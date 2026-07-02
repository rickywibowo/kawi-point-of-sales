<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\Business;
use App\Models\GoodsReceipt;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\Sale;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AccountingService
{
    public function __construct(private readonly AuditLogger $audit)
    {
    }

    public function postJournal(Business $business, ?int $branchId, array $data, ?Request $request = null): JournalEntry
    {
        return DB::transaction(function () use ($business, $branchId, $data, $request): JournalEntry {
            $lines = collect($data['lines'])->map(function (array $line) use ($business): array {
                $account = Account::query()->forBusiness($business->id)->whereKey($line['account_id'])->first();

                if (! $account) {
                    throw ValidationException::withMessages(['lines' => ['One or more accounts are outside the active business.']]);
                }

                return [
                    'account_id' => $account->id,
                    'description' => $line['description'] ?? null,
                    'debit' => round((float) ($line['debit'] ?? 0), 2),
                    'credit' => round((float) ($line['credit'] ?? 0), 2),
                ];
            });

            $totalDebit = round($lines->sum('debit'), 2);
            $totalCredit = round($lines->sum('credit'), 2);

            if ($totalDebit <= 0 || $totalDebit !== $totalCredit) {
                throw ValidationException::withMessages(['lines' => ['Journal entry must be balanced.']]);
            }

            $journal = JournalEntry::query()->create([
                'business_id' => $business->id,
                'branch_id' => $branchId,
                'accounting_period_id' => $data['accounting_period_id'] ?? null,
                'uuid' => (string) Str::uuid(),
                'journal_number' => $data['journal_number'],
                'journal_date' => $data['journal_date'] ?? now()->toDateString(),
                'status' => $data['status'] ?? 'posted',
                'source_type' => $data['source_type'] ?? null,
                'source_id' => $data['source_id'] ?? null,
                'description' => $data['description'] ?? null,
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'posted_at' => ($data['status'] ?? 'posted') === 'posted' ? now() : null,
                'posted_by' => $request?->user()?->id,
            ]);

            foreach ($lines as $line) {
                $journal->lines()->create($line);
            }

            $journal->load('lines.account');
            $this->audit->record('journal.posted', $journal, after: $journal->toArray(), request: $request);

            return $journal;
        });
    }

    public function postSaleJournal(Sale $sale, ?Request $request = null): JournalEntry
    {
        $business = Business::query()->findOrFail($sale->business_id);

        if ($this->journalExists(Sale::class, $sale->id)) {
            return JournalEntry::query()->where('source_type', Sale::class)->where('source_id', $sale->id)->firstOrFail();
        }

        $accounts = $this->accounts($sale->business_id);
        $sale->loadMissing('items.product');
        $cogs = $sale->items->sum(fn ($item) => (float) $item->quantity * (float) $item->product?->cost_price);
        $revenue = (float) $sale->subtotal - (float) $sale->discount_total + (float) $sale->service_charge_total;

        $lines = [
            ['account_id' => $accounts['cash']->id, 'debit' => (float) $sale->paid_total, 'credit' => 0, 'description' => 'Kas penjualan '.$sale->sale_number],
            ['account_id' => $accounts['sales']->id, 'debit' => 0, 'credit' => $revenue, 'description' => 'Penjualan '.$sale->sale_number],
        ];

        if ((float) $sale->tax_total > 0) {
            $lines[] = ['account_id' => $accounts['output_tax']->id, 'debit' => 0, 'credit' => (float) $sale->tax_total, 'description' => 'Pajak keluaran '.$sale->sale_number];
        }

        if ($cogs > 0) {
            $lines[] = ['account_id' => $accounts['cogs']->id, 'debit' => $cogs, 'credit' => 0, 'description' => 'HPP '.$sale->sale_number];
            $lines[] = ['account_id' => $accounts['inventory']->id, 'debit' => 0, 'credit' => $cogs, 'description' => 'Persediaan keluar '.$sale->sale_number];
        }

        return $this->postJournal($business, $sale->branch_id, [
            'journal_number' => 'JE-SALE-'.$sale->sale_number,
            'journal_date' => $sale->sold_at?->toDateString() ?? now()->toDateString(),
            'source_type' => Sale::class,
            'source_id' => $sale->id,
            'description' => 'Auto journal sale '.$sale->sale_number,
            'lines' => $this->normalizeBalancedLines($lines),
        ], $request);
    }

    public function postGoodsReceiptJournal(GoodsReceipt $receipt, ?Request $request = null): JournalEntry
    {
        $business = Business::query()->findOrFail($receipt->business_id);

        if ($this->journalExists(GoodsReceipt::class, $receipt->id)) {
            return JournalEntry::query()->where('source_type', GoodsReceipt::class)->where('source_id', $receipt->id)->firstOrFail();
        }

        $accounts = $this->accounts($receipt->business_id);
        $lines = [
            ['account_id' => $accounts['inventory']->id, 'debit' => (float) $receipt->subtotal, 'credit' => 0, 'description' => 'Persediaan masuk '.$receipt->receipt_number],
            ['account_id' => $accounts['accounts_payable']->id, 'debit' => 0, 'credit' => (float) $receipt->grand_total, 'description' => 'Utang supplier '.$receipt->receipt_number],
        ];

        if ((float) $receipt->tax_total > 0) {
            $lines[] = ['account_id' => $accounts['input_tax']->id, 'debit' => (float) $receipt->tax_total, 'credit' => 0, 'description' => 'Pajak masukan '.$receipt->receipt_number];
        }

        return $this->postJournal($business, $receipt->branch_id, [
            'journal_number' => 'JE-GR-'.$receipt->receipt_number,
            'journal_date' => $receipt->received_date?->toDateString() ?? now()->toDateString(),
            'source_type' => GoodsReceipt::class,
            'source_id' => $receipt->id,
            'description' => 'Auto journal goods receipt '.$receipt->receipt_number,
            'lines' => $this->normalizeBalancedLines($lines),
        ], $request);
    }

    public function trialBalance(int $businessId): Collection
    {
        return Account::query()
            ->forBusiness($businessId)
            ->withSum(['journalLines as debit_total' => fn ($query) => $query->whereHas('journalEntry', fn ($query) => $query->where('status', 'posted'))], 'debit')
            ->withSum(['journalLines as credit_total' => fn ($query) => $query->whereHas('journalEntry', fn ($query) => $query->where('status', 'posted'))], 'credit')
            ->orderBy('code')
            ->get()
            ->map(function (Account $account): array {
                $debit = (float) ($account->debit_total ?? 0);
                $credit = (float) ($account->credit_total ?? 0);

                return [
                    'code' => $account->code,
                    'name' => $account->name,
                    'type' => $account->type,
                    'debit' => round($debit, 2),
                    'credit' => round($credit, 2),
                    'balance' => round($account->normal_balance === 'debit' ? $debit - $credit : $credit - $debit, 2),
                ];
            });
    }

    public function profitAndLoss(int $businessId): array
    {
        $trialBalance = $this->trialBalance($businessId);
        $revenue = $trialBalance->where('type', 'revenue')->sum('balance');
        $expenses = $trialBalance->whereIn('type', ['expense', 'cost_of_goods_sold'])->sum('balance');

        return [
            'revenue' => round($revenue, 2),
            'expenses' => round($expenses, 2),
            'net_profit' => round($revenue - $expenses, 2),
        ];
    }

    public function generalLedger(int $businessId, ?string $dateFrom = null, ?string $dateTo = null): Collection
    {
        return Account::query()
            ->forBusiness($businessId)
            ->with(['journalLines' => function ($query) use ($dateFrom, $dateTo): void {
                $query
                    ->whereHas('journalEntry', function ($query) use ($dateFrom, $dateTo): void {
                        $query->where('status', 'posted');

                        if ($dateFrom) {
                            $query->whereDate('journal_date', '>=', $dateFrom);
                        }

                        if ($dateTo) {
                            $query->whereDate('journal_date', '<=', $dateTo);
                        }
                    })
                    ->with('journalEntry')
                    ->orderBy('id');
            }])
            ->orderBy('code')
            ->get()
            ->map(function (Account $account): array {
                $runningBalance = 0.0;
                $lines = $account->journalLines
                    ->sortBy(fn (JournalLine $line) => $line->journalEntry?->journal_date?->format('Y-m-d').'-'.$line->id)
                    ->values()
                    ->map(function (JournalLine $line) use ($account, &$runningBalance): array {
                        $debit = (float) $line->debit;
                        $credit = (float) $line->credit;
                        $delta = $account->normal_balance === 'debit' ? $debit - $credit : $credit - $debit;
                        $runningBalance += $delta;

                        return [
                            'journal_number' => $line->journalEntry?->journal_number,
                            'journal_date' => $line->journalEntry?->journal_date?->toDateString(),
                            'description' => $line->description ?? $line->journalEntry?->description,
                            'debit' => round($debit, 2),
                            'credit' => round($credit, 2),
                            'running_balance' => round($runningBalance, 2),
                        ];
                    });

                return [
                    'account' => [
                        'code' => $account->code,
                        'name' => $account->name,
                        'type' => $account->type,
                        'normal_balance' => $account->normal_balance,
                    ],
                    'debit_total' => round($lines->sum('debit'), 2),
                    'credit_total' => round($lines->sum('credit'), 2),
                    'ending_balance' => round($runningBalance, 2),
                    'lines' => $lines,
                ];
            });
    }

    public function balanceSheet(int $businessId): array
    {
        $trialBalance = $this->trialBalance($businessId);
        $assets = $this->statementSection($trialBalance, ['asset']);
        $liabilities = $this->statementSection($trialBalance, ['liability']);
        $equityBase = $this->statementSection($trialBalance, ['equity']);
        $netProfit = $this->profitAndLoss($businessId)['net_profit'];
        $equity = [
            'lines' => $equityBase['lines']->push([
                'code' => '3900',
                'name' => 'Laba Ditahan Periode Berjalan',
                'type' => 'equity',
                'balance' => round($netProfit, 2),
            ])->values(),
            'total' => round($equityBase['total'] + $netProfit, 2),
        ];

        return [
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equity' => $equity,
            'liabilities_and_equity_total' => round($liabilities['total'] + $equity['total'], 2),
            'is_balanced' => round($assets['total'] - ($liabilities['total'] + $equity['total']), 2) === 0.0,
        ];
    }

    public function cashFlow(int $businessId, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $cashLines = JournalLine::query()
            ->whereHas('account', fn ($query) => $query->forBusiness($businessId)->where('is_cash', true))
            ->whereHas('journalEntry', function ($query) use ($businessId, $dateFrom, $dateTo): void {
                $query->where('business_id', $businessId)->where('status', 'posted');

                if ($dateFrom) {
                    $query->whereDate('journal_date', '>=', $dateFrom);
                }

                if ($dateTo) {
                    $query->whereDate('journal_date', '<=', $dateTo);
                }
            })
            ->with(['account', 'journalEntry.lines.account'])
            ->get();

        $inflows = round($cashLines->sum(fn (JournalLine $line) => (float) $line->debit), 2);
        $outflows = round($cashLines->sum(fn (JournalLine $line) => (float) $line->credit), 2);

        return [
            'period' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
            'operating' => [
                'inflows' => $inflows,
                'outflows' => $outflows,
                'net_cash_flow' => round($inflows - $outflows, 2),
            ],
            'ending_cash_balance' => round($this->trialBalance($businessId)->whereIn('code', $this->cashAccountCodes($businessId))->sum('balance'), 2),
        ];
    }

    private function accounts(int $businessId): array
    {
        $accounts = Account::query()->forBusiness($businessId)->get()->keyBy('code');

        return [
            'cash' => $accounts['1100'],
            'inventory' => $accounts['1300'],
            'input_tax' => $accounts['1400'],
            'accounts_payable' => $accounts['2100'],
            'output_tax' => $accounts['2200'],
            'sales' => $accounts['4100'],
            'cogs' => $accounts['5100'],
        ];
    }

    private function journalExists(string $sourceType, int $sourceId): bool
    {
        return JournalEntry::query()->where('source_type', $sourceType)->where('source_id', $sourceId)->exists();
    }

    private function normalizeBalancedLines(array $lines): array
    {
        return collect($lines)
            ->filter(fn (array $line) => round((float) $line['debit'] + (float) $line['credit'], 2) > 0)
            ->values()
            ->all();
    }

    private function statementSection(Collection $trialBalance, array $types): array
    {
        $lines = $trialBalance
            ->whereIn('type', $types)
            ->values()
            ->map(fn (array $line): array => [
                'code' => $line['code'],
                'name' => $line['name'],
                'type' => $line['type'],
                'balance' => $line['balance'],
            ]);

        return [
            'lines' => $lines,
            'total' => round($lines->sum('balance'), 2),
        ];
    }

    private function cashAccountCodes(int $businessId): array
    {
        return Account::query()
            ->forBusiness($businessId)
            ->where('is_cash', true)
            ->pluck('code')
            ->all();
    }
}
