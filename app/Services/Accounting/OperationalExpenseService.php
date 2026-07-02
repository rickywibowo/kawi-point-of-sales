<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\Business;
use App\Models\OperationalExpense;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OperationalExpenseService
{
    public function __construct(
        private readonly AccountingService $accounting,
        private readonly AuditLogger $audit,
    ) {
    }

    public function post(Business $business, ?int $branchId, array $data, ?Request $request = null): OperationalExpense
    {
        return DB::transaction(function () use ($business, $branchId, $data, $request): OperationalExpense {
            $expenseAccount = Account::query()
                ->forBusiness($business->id)
                ->whereKey($data['account_id'])
                ->whereIn('type', ['expense', 'cost_of_goods_sold'])
                ->first();

            if (! $expenseAccount) {
                throw ValidationException::withMessages(['account_id' => ['Expense account must belong to the active business.']]);
            }

            $cashAccount = Account::query()
                ->forBusiness($business->id)
                ->whereKey($data['cash_account_id'] ?? null)
                ->where('is_cash', true)
                ->first()
                ?? Account::query()->forBusiness($business->id)->where('code', '1100')->where('is_cash', true)->first();

            if (! $cashAccount) {
                throw ValidationException::withMessages(['cash_account_id' => ['Cash account must belong to the active business.']]);
            }

            $expense = OperationalExpense::query()->create([
                'business_id' => $business->id,
                'branch_id' => $branchId,
                'account_id' => $expenseAccount->id,
                'cash_account_id' => $cashAccount->id,
                'uuid' => (string) Str::uuid(),
                'expense_number' => $data['expense_number'],
                'expense_date' => $data['expense_date'] ?? now()->toDateString(),
                'category' => $data['category'] ?? null,
                'payee' => $data['payee'] ?? null,
                'description' => $data['description'] ?? null,
                'amount' => round((float) $data['amount'], 2),
                'payment_method' => $data['payment_method'] ?? 'cash',
                'reference_number' => $data['reference_number'] ?? null,
                'posted_at' => now(),
                'posted_by' => $request?->user()?->id,
            ]);

            $this->accounting->postJournal($business, $branchId, [
                'journal_number' => 'JE-EXP-'.$expense->expense_number,
                'journal_date' => $expense->expense_date->toDateString(),
                'source_type' => OperationalExpense::class,
                'source_id' => $expense->id,
                'description' => 'Operational expense '.$expense->expense_number,
                'lines' => [
                    ['account_id' => $expenseAccount->id, 'debit' => (float) $expense->amount, 'credit' => 0, 'description' => $expense->description],
                    ['account_id' => $cashAccount->id, 'debit' => 0, 'credit' => (float) $expense->amount, 'description' => 'Kas keluar '.$expense->expense_number],
                ],
            ], $request);

            $expense->load(['account', 'cashAccount']);
            $this->audit->record('expense.posted', $expense, after: $expense->toArray(), request: $request);

            return $expense;
        });
    }
}
