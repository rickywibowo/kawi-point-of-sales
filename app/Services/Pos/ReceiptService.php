<?php

namespace App\Services\Pos;

use App\Models\Business;
use App\Models\Sale;

class ReceiptService
{
    public function build(Business $business, ?int $branchId, Sale $sale): array
    {
        abort_unless($sale->business_id === $business->id && ($branchId === null || $sale->branch_id === $branchId), 403);

        $sale->loadMissing([
            'branch',
            'cashierShift',
            'customer',
            'diningTable',
            'items.modifiers',
            'payments',
        ]);

        return [
            'business' => [
                'name' => $business->name,
                'legal_name' => $business->legal_name,
                'tax_number' => $business->tax_number,
                'currency' => $business->currency,
            ],
            'branch' => [
                'name' => $sale->branch?->name,
                'code' => $sale->branch?->code,
                'address' => $sale->branch?->address,
                'phone' => $sale->branch?->phone,
            ],
            'sale' => [
                'uuid' => $sale->uuid,
                'sale_number' => $sale->sale_number,
                'type' => $sale->type,
                'status' => $sale->status,
                'sold_at' => $sale->sold_at?->toIso8601String(),
                'cashier_shift' => $sale->cashierShift?->shift_number,
                'customer' => $sale->customer?->name,
                'dining_table' => $sale->diningTable ? [
                    'code' => $sale->diningTable->code,
                    'name' => $sale->diningTable->name,
                    'section' => $sale->diningTable->section,
                ] : null,
            ],
            'items' => $sale->items->map(fn ($item): array => [
                'name' => $item->product_name,
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'discount_total' => (float) $item->discount_total,
                'tax_total' => (float) $item->tax_total,
                'line_total' => (float) $item->line_total,
                'notes' => $item->notes,
                'modifiers' => $item->modifiers->map(fn ($modifier): array => [
                    'name' => $modifier->modifier_name,
                    'price_delta' => (float) $modifier->price_delta,
                ])->values(),
            ])->values(),
            'payments' => $sale->payments->map(fn ($payment): array => [
                'method' => $payment->method,
                'amount' => (float) $payment->amount,
                'reference' => $payment->reference,
            ])->values(),
            'totals' => [
                'subtotal' => (float) $sale->subtotal,
                'discount_total' => (float) $sale->discount_total,
                'tax_total' => (float) $sale->tax_total,
                'service_charge_total' => (float) $sale->service_charge_total,
                'grand_total' => (float) $sale->grand_total,
                'paid_total' => (float) $sale->paid_total,
                'change_total' => (float) $sale->change_total,
            ],
            'digital' => [
                'receipt_id' => $sale->uuid,
                'qr_payload' => 'KAWI-POS:'.$sale->uuid,
            ],
        ];
    }
}
