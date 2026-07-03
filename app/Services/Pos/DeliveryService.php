<?php

namespace App\Services\Pos;

use App\Models\Business;
use App\Models\DeliveryOrder;
use App\Models\Sale;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeliveryService
{
    public function __construct(private readonly AuditLogger $audit)
    {
    }

    public function createForSale(Sale $sale, array $data, ?Request $request = null): DeliveryOrder
    {
        if (DeliveryOrder::query()->where('sale_id', $sale->id)->exists()) {
            return DeliveryOrder::query()->where('sale_id', $sale->id)->firstOrFail();
        }

        return DB::transaction(function () use ($sale, $data, $request): DeliveryOrder {
            $delivery = DeliveryOrder::query()->create([
                'business_id' => $sale->business_id,
                'branch_id' => $sale->branch_id,
                'sale_id' => $sale->id,
                'delivery_number' => 'DO-'.$sale->sale_number,
                'status' => 'pending',
                'recipient_name' => $data['delivery']['recipient_name'],
                'recipient_phone' => $data['delivery']['recipient_phone'] ?? null,
                'address' => $data['delivery']['address'],
                'notes' => $data['delivery']['notes'] ?? null,
                'delivery_fee' => round((float) ($data['delivery']['fee'] ?? 0), 2),
            ]);

            $this->audit->record('delivery_order.created', $delivery, after: $delivery->toArray(), request: $request);

            return $delivery;
        });
    }

    public function updateStatus(Business $business, ?int $branchId, DeliveryOrder $delivery, array $data, Request $request): DeliveryOrder
    {
        abort_unless($delivery->business_id === $business->id && ($branchId === null || $delivery->branch_id === $branchId), 403);

        if ($delivery->status === 'delivered' && $data['status'] !== 'delivered') {
            throw ValidationException::withMessages(['status' => ['Delivered orders cannot move back to another status.']]);
        }

        $before = $delivery->toArray();
        $payload = ['status' => $data['status']];

        if ($data['status'] === 'assigned') {
            $payload['courier_name'] = $data['courier_name'] ?? $delivery->courier_name;
            $payload['courier_phone'] = $data['courier_phone'] ?? $delivery->courier_phone;
            $payload['assigned_at'] = $delivery->assigned_at ?? now();
        }

        if ($data['status'] === 'picked_up') {
            $payload['picked_up_at'] = $delivery->picked_up_at ?? now();
        }

        if ($data['status'] === 'delivered') {
            $payload['delivered_at'] = $delivery->delivered_at ?? now();
        }

        if ($data['status'] === 'cancelled') {
            $payload['cancelled_at'] = $delivery->cancelled_at ?? now();
        }

        $delivery->update($payload);
        $this->audit->record('delivery_order.status_updated', $delivery, before: $before, after: $delivery->fresh()->toArray(), request: $request);

        return $delivery->fresh(['sale']);
    }
}
