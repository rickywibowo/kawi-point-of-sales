<?php

namespace App\Services\Pos;

use App\Models\Business;
use App\Models\Promotion;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PromotionService
{
    public function __construct(private readonly AuditLogger $audit)
    {
    }

    public function create(Business $business, array $data, Request $request): Promotion
    {
        $exists = Promotion::query()
            ->forBusiness($business->id)
            ->where('code', strtoupper($data['code']))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages(['code' => ['Promotion code already exists in this business.']]);
        }

        return DB::transaction(function () use ($business, $data, $request): Promotion {
            $promotion = Promotion::query()->create([
                'business_id' => $business->id,
                'code' => strtoupper($data['code']),
                'name' => $data['name'],
                'type' => $data['type'],
                'value' => round((float) $data['value'], 2),
                'minimum_subtotal' => round((float) ($data['minimum_subtotal'] ?? 0), 2),
                'maximum_discount' => isset($data['maximum_discount']) ? round((float) $data['maximum_discount'], 2) : null,
                'usage_limit' => $data['usage_limit'] ?? null,
                'starts_on' => $data['starts_on'] ?? null,
                'ends_on' => $data['ends_on'] ?? null,
                'is_active' => $data['is_active'] ?? true,
            ]);

            $this->audit->record('promotion.created', $promotion, after: $promotion->toArray(), request: $request);

            return $promotion;
        });
    }

    public function apply(Business $business, ?string $code, float $eligibleSubtotal): array
    {
        if (! $code) {
            return [null, 0.0];
        }

        $promotion = Promotion::query()
            ->forBusiness($business->id)
            ->where('code', strtoupper($code))
            ->first();

        if (! $promotion || ! $promotion->is_active) {
            throw ValidationException::withMessages(['promotion_code' => ['Promotion code is not active.']]);
        }

        $today = Carbon::today();

        if ($promotion->starts_on && $today->lt($promotion->starts_on)) {
            throw ValidationException::withMessages(['promotion_code' => ['Promotion has not started yet.']]);
        }

        if ($promotion->ends_on && $today->gt($promotion->ends_on)) {
            throw ValidationException::withMessages(['promotion_code' => ['Promotion has expired.']]);
        }

        if ($promotion->usage_limit !== null && $promotion->usage_count >= $promotion->usage_limit) {
            throw ValidationException::withMessages(['promotion_code' => ['Promotion usage limit has been reached.']]);
        }

        if ($eligibleSubtotal < (float) $promotion->minimum_subtotal) {
            throw ValidationException::withMessages(['promotion_code' => ['Sale subtotal does not meet promotion minimum.']]);
        }

        $discount = $promotion->type === 'percent'
            ? round($eligibleSubtotal * ((float) $promotion->value / 100), 2)
            : round((float) $promotion->value, 2);

        if ($promotion->maximum_discount !== null) {
            $discount = min($discount, (float) $promotion->maximum_discount);
        }

        return [$promotion, min($discount, $eligibleSubtotal)];
    }

    public function markUsed(Promotion $promotion): void
    {
        $promotion->increment('usage_count');
    }
}
