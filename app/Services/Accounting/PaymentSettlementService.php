<?php

namespace App\Services\Accounting;

use App\Models\Business;
use App\Models\PaymentSettlement;
use App\Models\SalePayment;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentSettlementService
{
    public function __construct(private readonly AuditLogger $audit)
    {
    }

    public function create(Business $business, ?int $branchId, array $data, Request $request): PaymentSettlement
    {
        $exists = PaymentSettlement::query()
            ->where('business_id', $business->id)
            ->where('settlement_number', $data['settlement_number'])
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages(['settlement_number' => ['Settlement number already exists in this business.']]);
        }

        $from = Carbon::parse($data['date_from'])->startOfDay();
        $to = Carbon::parse($data['date_to'])->endOfDay();

        $payments = SalePayment::query()
            ->where('sale_payments.business_id', $business->id)
            ->when($branchId, fn ($query) => $query->where('sale_payments.branch_id', $branchId))
            ->where('sale_payments.method', $data['method'])
            ->whereHas('sale', fn ($query) => $query->where('status', 'completed')->whereBetween('sold_at', [$from, $to]))
            ->whereDoesntHave('settlementItem')
            ->with('sale')
            ->get();

        if ($payments->isEmpty()) {
            throw ValidationException::withMessages(['method' => ['No unsettled payments found for this method and period.']]);
        }

        return DB::transaction(function () use ($business, $branchId, $data, $from, $to, $payments, $request): PaymentSettlement {
            $expected = round((float) $payments->sum(fn (SalePayment $payment) => (float) $payment->amount), 2);
            $reported = round((float) $data['reported_amount'], 2);
            $settlement = PaymentSettlement::query()->create([
                'business_id' => $business->id,
                'branch_id' => $branchId,
                'settlement_number' => $data['settlement_number'],
                'method' => $data['method'],
                'date_from' => $from->toDateString(),
                'date_to' => $to->toDateString(),
                'expected_amount' => $expected,
                'reported_amount' => $reported,
                'variance_amount' => round($reported - $expected, 2),
                'status' => 'posted',
                'notes' => $data['notes'] ?? null,
                'posted_at' => now(),
                'posted_by' => $request->user()?->id,
            ]);

            foreach ($payments as $payment) {
                $settlement->items()->create([
                    'sale_payment_id' => $payment->id,
                    'sale_id' => $payment->sale_id,
                    'amount' => $payment->amount,
                    'reference' => $payment->reference,
                ]);
            }

            $settlement->load(['items.salePayment', 'items.sale']);
            $this->audit->record('payment_settlement.posted', $settlement, after: $settlement->toArray(), request: $request);

            return $settlement;
        });
    }
}
