<?php

namespace App\Services\Production;

use App\Models\Business;
use App\Models\ProductionOrder;
use App\Models\Recipe;
use App\Models\StockBalance;
use App\Models\StockLedger;
use App\Models\Warehouse;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductionService
{
    public function __construct(private readonly AuditLogger $audit)
    {
    }

    public function postProduction(Business $business, array $data, Request $request): ProductionOrder
    {
        $warehouse = Warehouse::query()
            ->where('business_id', $business->id)
            ->whereKey($data['warehouse_id'])
            ->first();

        if (! $warehouse) {
            throw ValidationException::withMessages(['warehouse_id' => ['The selected warehouse is outside the active business.']]);
        }

        $recipe = Recipe::query()
            ->forBusiness($business->id)
            ->whereKey($data['recipe_id'])
            ->with(['product', 'items.ingredientProduct'])
            ->first();

        if (! $recipe) {
            throw ValidationException::withMessages(['recipe_id' => ['The selected recipe is outside the active business.']]);
        }

        return DB::transaction(function () use ($business, $warehouse, $recipe, $data, $request): ProductionOrder {
            $plannedQuantity = (float) $data['planned_quantity'];
            $actualQuantity = (float) ($data['actual_quantity'] ?? $plannedQuantity);
            $wasteQuantity = max($plannedQuantity - $actualQuantity, 0);

            $order = ProductionOrder::query()->create([
                'business_id' => $business->id,
                'branch_id' => $warehouse->branch_id,
                'warehouse_id' => $warehouse->id,
                'recipe_id' => $recipe->id,
                'product_id' => $recipe->product_id,
                'uuid' => (string) Str::uuid(),
                'production_number' => $data['production_number'],
                'status' => 'posted',
                'planned_quantity' => $plannedQuantity,
                'actual_quantity' => $actualQuantity,
                'waste_quantity' => $wasteQuantity,
                'produced_at' => now(),
                'produced_by' => $request->user()?->id,
                'notes' => $data['notes'] ?? null,
            ]);

            $totalCost = 0.0;

            foreach ($recipe->items as $item) {
                $quantity = ((float) $item->quantity / (float) $recipe->yield_quantity) * $plannedQuantity;
                $quantity *= 1 + ((float) $item->waste_percentage / 100);
                $unitCost = (float) $item->unit_cost;
                $lineCost = round($quantity * $unitCost, 2);
                $totalCost += $lineCost;

                $order->items()->create([
                    'product_id' => $item->ingredient_product_id,
                    'unit_of_measure_id' => $item->unit_of_measure_id,
                    'type' => 'ingredient',
                    'quantity' => $quantity,
                    'unit_cost' => $unitCost,
                    'total_cost' => $lineCost,
                ]);

                $this->recordMovement($business->id, $warehouse->branch_id, $warehouse->id, $item->ingredient_product_id, $item->unit_of_measure_id, 'production_consumption', -1 * $quantity, $unitCost, $order, $request->user()?->id);
            }

            $outputUnitCost = $actualQuantity > 0 ? round($totalCost / $actualQuantity, 2) : 0;
            $order->items()->create([
                'product_id' => $recipe->product_id,
                'unit_of_measure_id' => $recipe->yield_unit_id,
                'type' => 'output',
                'quantity' => $actualQuantity,
                'unit_cost' => $outputUnitCost,
                'total_cost' => round($actualQuantity * $outputUnitCost, 2),
            ]);

            $this->recordMovement($business->id, $warehouse->branch_id, $warehouse->id, $recipe->product_id, $recipe->yield_unit_id, 'production_output', $actualQuantity, $outputUnitCost, $order, $request->user()?->id);

            $order->update(['total_cost' => round($totalCost, 2)]);
            $order->load(['recipe', 'product', 'items.product']);
            $this->audit->record('production_order.posted', $order, after: $order->toArray(), request: $request);

            return $order;
        });
    }

    private function recordMovement(int $businessId, ?int $branchId, int $warehouseId, int $productId, ?int $unitId, string $type, float $quantityDelta, float $unitCost, ProductionOrder $order, ?int $userId): void
    {
        StockLedger::query()->create([
            'business_id' => $businessId,
            'branch_id' => $branchId,
            'warehouse_id' => $warehouseId,
            'product_id' => $productId,
            'unit_of_measure_id' => $unitId,
            'uuid' => (string) Str::uuid(),
            'movement_type' => $type,
            'quantity_in' => max($quantityDelta, 0),
            'quantity_out' => abs(min($quantityDelta, 0)),
            'unit_cost' => $unitCost,
            'total_cost' => abs($quantityDelta) * $unitCost,
            'source_type' => ProductionOrder::class,
            'source_id' => $order->id,
            'reference_number' => $order->production_number,
            'occurred_at' => now(),
            'created_by' => $userId,
        ]);

        $balance = StockBalance::query()->firstOrCreate(
            ['warehouse_id' => $warehouseId, 'product_id' => $productId],
            ['business_id' => $businessId, 'branch_id' => $branchId, 'quantity_on_hand' => 0, 'average_cost' => 0, 'stock_value' => 0],
        );

        $newQuantity = (float) $balance->quantity_on_hand + $quantityDelta;
        $newValue = (float) $balance->stock_value + ($quantityDelta * $unitCost);

        $balance->update([
            'quantity_on_hand' => $newQuantity,
            'average_cost' => $newQuantity > 0 ? round($newValue / $newQuantity, 2) : 0,
            'stock_value' => max(round($newValue, 2), 0),
        ]);
    }
}
