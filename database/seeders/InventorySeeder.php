<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Business;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\RecipeItem;
use App\Models\StockBalance;
use App\Models\StockLedger;
use App\Models\UnitConversion;
use App\Models\UnitOfMeasure;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class InventorySeeder extends Seeder
{
    public function run(): void
    {
        $business = Business::query()->where('name', 'KAWI Demo Business')->firstOrFail();
        $branch = Branch::query()->where('business_id', $business->id)->where('code', 'MAIN')->firstOrFail();
        $pcs = UnitOfMeasure::query()->where('business_id', $business->id)->where('code', 'PCS')->firstOrFail();
        $gram = UnitOfMeasure::query()->where('business_id', $business->id)->where('code', 'GRAM')->firstOrFail();
        $riceBowl = Product::query()->where('business_id', $business->id)->where('sku', 'KAWI-RICE-001')->firstOrFail();
        $coffee = Product::query()->where('business_id', $business->id)->where('sku', 'KAWI-COFFEE-001')->firstOrFail();

        $warehouse = Warehouse::query()->firstOrCreate(
            ['business_id' => $business->id, 'code' => 'MAIN-WH'],
            [
                'branch_id' => $branch->id,
                'uuid' => (string) Str::uuid(),
                'name' => 'Gudang Cabang Utama',
                'type' => 'branch',
            ],
        );

        UnitConversion::query()->firstOrCreate(
            ['business_id' => $business->id, 'from_unit_id' => $pcs->id, 'to_unit_id' => $gram->id],
            ['multiplier' => 1],
        );

        $recipe = Recipe::query()->firstOrCreate(
            ['business_id' => $business->id, 'product_id' => $riceBowl->id, 'version' => 1],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Recipe KAWI Rice Bowl',
                'yield_quantity' => 1,
                'yield_unit_id' => $pcs->id,
                'waste_percentage' => 2,
                'computed_cost' => 9000,
                'is_active' => true,
            ],
        );

        RecipeItem::query()->firstOrCreate(
            ['business_id' => $business->id, 'recipe_id' => $recipe->id, 'ingredient_product_id' => $coffee->id],
            [
                'quantity' => 1,
                'unit_of_measure_id' => $pcs->id,
                'waste_percentage' => 0,
                'unit_cost' => 9000,
                'line_cost' => 9000,
            ],
        );

        foreach ([[$riceBowl, 25, 18000], [$coffee, 40, 9000]] as [$product, $quantity, $unitCost]) {
            StockBalance::query()->firstOrCreate(
                ['warehouse_id' => $warehouse->id, 'product_id' => $product->id],
                [
                    'business_id' => $business->id,
                    'branch_id' => $branch->id,
                    'quantity_on_hand' => $quantity,
                    'average_cost' => $unitCost,
                    'stock_value' => $quantity * $unitCost,
                ],
            );

            StockLedger::query()->firstOrCreate(
                ['business_id' => $business->id, 'reference_number' => 'OPENING-STOCK', 'product_id' => $product->id],
                [
                    'branch_id' => $branch->id,
                    'warehouse_id' => $warehouse->id,
                    'unit_of_measure_id' => $pcs->id,
                    'uuid' => (string) Str::uuid(),
                    'movement_type' => 'opening_balance',
                    'quantity_in' => $quantity,
                    'quantity_out' => 0,
                    'unit_cost' => $unitCost,
                    'total_cost' => $quantity * $unitCost,
                    'source_type' => 'seed',
                    'source_id' => 0,
                    'occurred_at' => now(),
                ],
            );
        }
    }
}
