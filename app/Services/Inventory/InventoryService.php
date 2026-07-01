<?php

namespace App\Services\Inventory;

use App\Models\Business;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\RecipeItem;
use App\Models\StockAdjustment;
use App\Models\StockBalance;
use App\Models\StockLedger;
use App\Models\Warehouse;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public function __construct(private readonly AuditLogger $audit)
    {
    }

    public function createRecipe(Business $business, array $data, Request $request): Recipe
    {
        $this->assertBusinessProduct($business->id, $data['product_id'], 'product_id');

        foreach ($data['items'] as $index => $item) {
            $this->assertBusinessProduct($business->id, $item['ingredient_product_id'], "items.$index.ingredient_product_id");
        }

        return DB::transaction(function () use ($business, $data, $request): Recipe {
            $recipe = Recipe::query()->create([
                'business_id' => $business->id,
                'product_id' => $data['product_id'],
                'uuid' => (string) Str::uuid(),
                'name' => $data['name'],
                'yield_quantity' => $data['yield_quantity'] ?? 1,
                'yield_unit_id' => $data['yield_unit_id'] ?? null,
                'waste_percentage' => $data['waste_percentage'] ?? 0,
                'version' => $data['version'] ?? 1,
                'is_active' => $data['is_active'] ?? true,
            ]);

            $computedCost = 0;

            foreach ($data['items'] as $item) {
                $ingredient = Product::query()->findOrFail($item['ingredient_product_id']);
                $unitCost = $item['unit_cost'] ?? $ingredient->cost_price;
                $wasteMultiplier = 1 + (($item['waste_percentage'] ?? 0) / 100);
                $lineCost = round((float) $item['quantity'] * (float) $unitCost * $wasteMultiplier, 2);
                $computedCost += $lineCost;

                RecipeItem::query()->create([
                    'business_id' => $business->id,
                    'recipe_id' => $recipe->id,
                    'ingredient_product_id' => $ingredient->id,
                    'quantity' => $item['quantity'],
                    'unit_of_measure_id' => $item['unit_of_measure_id'] ?? $ingredient->unit_of_measure_id,
                    'waste_percentage' => $item['waste_percentage'] ?? 0,
                    'unit_cost' => $unitCost,
                    'line_cost' => $lineCost,
                ]);
            }

            $recipe->update(['computed_cost' => $computedCost]);
            $recipe->load(['product', 'items.ingredientProduct']);
            $this->audit->record('recipe.created', $recipe, after: $recipe->toArray(), request: $request);

            return $recipe;
        });
    }

    public function postAdjustment(Business $business, ?int $branchId, array $data, Request $request): StockAdjustment
    {
        $warehouse = Warehouse::query()
            ->where('business_id', $business->id)
            ->whereKey($data['warehouse_id'])
            ->first();

        if (! $warehouse) {
            throw ValidationException::withMessages(['warehouse_id' => ['The selected warehouse is outside the active business.']]);
        }

        foreach ($data['items'] as $index => $item) {
            $this->assertBusinessProduct($business->id, $item['product_id'], "items.$index.product_id");
        }

        return DB::transaction(function () use ($business, $branchId, $warehouse, $data, $request): StockAdjustment {
            $adjustment = StockAdjustment::query()->create([
                'business_id' => $business->id,
                'branch_id' => $branchId ?? $warehouse->branch_id,
                'warehouse_id' => $warehouse->id,
                'uuid' => (string) Str::uuid(),
                'adjustment_number' => $data['adjustment_number'],
                'status' => 'posted',
                'notes' => $data['notes'] ?? null,
                'posted_by' => $request->user()?->id,
                'posted_at' => now(),
            ]);

            foreach ($data['items'] as $item) {
                $quantityDelta = (float) $item['quantity_delta'];
                $unitCost = (float) ($item['unit_cost'] ?? 0);

                $adjustment->items()->create([
                    'product_id' => $item['product_id'],
                    'unit_of_measure_id' => $item['unit_of_measure_id'] ?? null,
                    'quantity_delta' => $quantityDelta,
                    'unit_cost' => $unitCost,
                    'notes' => $item['notes'] ?? null,
                ]);

                $this->recordStockMovement(
                    businessId: $business->id,
                    branchId: $adjustment->branch_id,
                    warehouseId: $warehouse->id,
                    productId: $item['product_id'],
                    unitOfMeasureId: $item['unit_of_measure_id'] ?? null,
                    movementType: 'adjustment',
                    quantityDelta: $quantityDelta,
                    unitCost: $unitCost,
                    sourceType: StockAdjustment::class,
                    sourceId: $adjustment->id,
                    referenceNumber: $adjustment->adjustment_number,
                    notes: $item['notes'] ?? $adjustment->notes,
                    userId: $request->user()?->id,
                );
            }

            $adjustment->load('items.product');
            $this->audit->record('stock_adjustment.posted', $adjustment, after: $adjustment->toArray(), request: $request);

            return $adjustment;
        });
    }

    private function recordStockMovement(
        int $businessId,
        ?int $branchId,
        int $warehouseId,
        int $productId,
        ?int $unitOfMeasureId,
        string $movementType,
        float $quantityDelta,
        float $unitCost,
        string $sourceType,
        int $sourceId,
        string $referenceNumber,
        ?string $notes,
        ?int $userId,
    ): void {
        StockLedger::query()->create([
            'business_id' => $businessId,
            'branch_id' => $branchId,
            'warehouse_id' => $warehouseId,
            'product_id' => $productId,
            'unit_of_measure_id' => $unitOfMeasureId,
            'uuid' => (string) Str::uuid(),
            'movement_type' => $movementType,
            'quantity_in' => max($quantityDelta, 0),
            'quantity_out' => abs(min($quantityDelta, 0)),
            'unit_cost' => $unitCost,
            'total_cost' => abs($quantityDelta) * $unitCost,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'reference_number' => $referenceNumber,
            'notes' => $notes,
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

    private function assertBusinessProduct(int $businessId, int $productId, string $field): void
    {
        $exists = Product::query()->forBusiness($businessId)->whereKey($productId)->exists();

        if (! $exists) {
            throw ValidationException::withMessages([$field => ['The selected product is outside the active business.']]);
        }
    }
}
