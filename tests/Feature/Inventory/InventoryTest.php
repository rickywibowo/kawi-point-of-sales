<?php

namespace Tests\Feature\Inventory;

use App\Models\Branch;
use App\Models\Business;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\StockBalance;
use App\Models\StockLedger;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_index_returns_ledger_balances_and_recipes_for_active_tenant(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::query()->where('email', 'owner@kawi.test')->firstOrFail();
        $business = Business::query()->where('name', 'KAWI Demo Business')->firstOrFail();
        $branch = Branch::query()->where('business_id', $business->id)->firstOrFail();

        $response = $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Business-Id' => $business->uuid, 'X-Branch-Id' => $branch->uuid])
            ->getJson('/api/inventory');

        $response->assertOk();
        $this->assertStringContainsString('Gudang Cabang Utama', $response->getContent());
        $this->assertStringContainsString('Recipe KAWI Rice Bowl', $response->getContent());
    }

    public function test_post_stock_adjustment_creates_ledger_and_updates_balance(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::query()->where('email', 'owner@kawi.test')->firstOrFail();
        $business = Business::query()->where('name', 'KAWI Demo Business')->firstOrFail();
        $branch = Branch::query()->where('business_id', $business->id)->firstOrFail();
        $warehouse = Warehouse::query()->where('business_id', $business->id)->firstOrFail();
        $product = Product::query()->where('business_id', $business->id)->where('sku', 'KAWI-RICE-001')->firstOrFail();
        $before = (float) StockBalance::query()->where('warehouse_id', $warehouse->id)->where('product_id', $product->id)->firstOrFail()->quantity_on_hand;

        $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Business-Id' => $business->uuid, 'X-Branch-Id' => $branch->uuid])
            ->postJson('/api/stock-adjustments', [
                'warehouse_id' => $warehouse->id,
                'adjustment_number' => 'ADJ-TEST-001',
                'notes' => 'Stock correction',
                'items' => [
                    ['product_id' => $product->id, 'quantity_delta' => 5, 'unit_cost' => 18000],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('adjustment.adjustment_number', 'ADJ-TEST-001');

        $this->assertDatabaseHas('stock_ledgers', [
            'reference_number' => 'ADJ-TEST-001',
            'movement_type' => 'adjustment',
        ]);

        $after = (float) StockBalance::query()->where('warehouse_id', $warehouse->id)->where('product_id', $product->id)->firstOrFail()->quantity_on_hand;
        $this->assertSame($before + 5, $after);
        $this->assertDatabaseHas('audit_logs', ['action' => 'stock_adjustment.posted']);
    }

    public function test_recipe_creation_rejects_ingredient_from_other_business(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::query()->where('email', 'owner@kawi.test')->firstOrFail();
        $business = Business::query()->where('name', 'KAWI Demo Business')->firstOrFail();
        $product = Product::query()->where('business_id', $business->id)->firstOrFail();
        $outsideBusiness = Business::query()->create(['uuid' => (string) Str::uuid(), 'name' => 'Outside Business']);
        $outsideProduct = Product::query()->create([
            'business_id' => $outsideBusiness->id,
            'uuid' => (string) Str::uuid(),
            'name' => 'Outside Ingredient',
            'type' => 'goods',
            'sku' => 'OUT-ING-001',
        ]);

        $this->actingAs($user, 'sanctum')
            ->withHeader('X-Business-Id', $business->uuid)
            ->postJson('/api/recipes', [
                'product_id' => $product->id,
                'name' => 'Invalid Recipe',
                'yield_quantity' => 1,
                'items' => [
                    ['ingredient_product_id' => $outsideProduct->id, 'quantity' => 1],
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['items.0.ingredient_product_id']);
    }

    public function test_recipe_creation_computes_cost_from_items(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::query()->where('email', 'owner@kawi.test')->firstOrFail();
        $business = Business::query()->where('name', 'KAWI Demo Business')->firstOrFail();
        $products = Product::query()->where('business_id', $business->id)->take(2)->get();

        $this->actingAs($user, 'sanctum')
            ->withHeader('X-Business-Id', $business->uuid)
            ->postJson('/api/recipes', [
                'product_id' => $products[0]->id,
                'name' => 'Costed Recipe',
                'yield_quantity' => 1,
                'items' => [
                    ['ingredient_product_id' => $products[1]->id, 'quantity' => 2, 'unit_cost' => 5000, 'waste_percentage' => 10],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('recipe.computed_cost', '11000.00');
    }

    public function test_stock_transfer_posts_out_and_in_ledgers_and_updates_balances(): void
    {
        $this->seed(DatabaseSeeder::class);

        [$user, $business, $branch] = $this->context();
        $fromWarehouse = Warehouse::query()->where('business_id', $business->id)->where('branch_id', $branch->id)->firstOrFail();
        $toWarehouse = Warehouse::query()->create([
            'business_id' => $business->id,
            'branch_id' => $branch->id,
            'uuid' => (string) Str::uuid(),
            'name' => 'Gudang Cadangan',
            'code' => 'RESERVE',
            'type' => 'branch',
        ]);
        $product = Product::query()->where('business_id', $business->id)->where('sku', 'KAWI-RICE-001')->firstOrFail();
        $beforeFrom = (float) StockBalance::query()->where('warehouse_id', $fromWarehouse->id)->where('product_id', $product->id)->firstOrFail()->quantity_on_hand;

        $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Business-Id' => $business->uuid, 'X-Branch-Id' => $branch->uuid])
            ->postJson('/api/stock-transfers', [
                'from_warehouse_id' => $fromWarehouse->id,
                'to_warehouse_id' => $toWarehouse->id,
                'transfer_number' => 'TRF-TEST-001',
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 3],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('transfer.status', 'posted');

        $afterFrom = (float) StockBalance::query()->where('warehouse_id', $fromWarehouse->id)->where('product_id', $product->id)->firstOrFail()->quantity_on_hand;
        $afterTo = (float) StockBalance::query()->where('warehouse_id', $toWarehouse->id)->where('product_id', $product->id)->firstOrFail()->quantity_on_hand;

        $this->assertSame($beforeFrom - 3, $afterFrom);
        $this->assertSame(3.0, $afterTo);
        $this->assertDatabaseHas('stock_ledgers', ['reference_number' => 'TRF-TEST-001', 'movement_type' => 'transfer_out']);
        $this->assertDatabaseHas('stock_ledgers', ['reference_number' => 'TRF-TEST-001', 'movement_type' => 'transfer_in']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'stock_transfer.posted']);
    }

    public function test_stock_opname_posts_variance_ledger_and_updates_balance(): void
    {
        $this->seed(DatabaseSeeder::class);

        [$user, $business, $branch] = $this->context();
        $warehouse = Warehouse::query()->where('business_id', $business->id)->where('branch_id', $branch->id)->firstOrFail();
        $product = Product::query()->where('business_id', $business->id)->where('sku', 'KAWI-RICE-001')->firstOrFail();
        $before = (float) StockBalance::query()->where('warehouse_id', $warehouse->id)->where('product_id', $product->id)->firstOrFail()->quantity_on_hand;
        $counted = $before - 2;

        $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Business-Id' => $business->uuid, 'X-Branch-Id' => $branch->uuid])
            ->postJson('/api/stock-opnames', [
                'warehouse_id' => $warehouse->id,
                'opname_number' => 'OPN-TEST-001',
                'items' => [
                    ['product_id' => $product->id, 'counted_quantity' => $counted],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('opname.items.0.variance_quantity', '-2.000000');

        $after = (float) StockBalance::query()->where('warehouse_id', $warehouse->id)->where('product_id', $product->id)->firstOrFail()->quantity_on_hand;

        $this->assertSame($counted, $after);
        $this->assertDatabaseHas('stock_ledgers', ['reference_number' => 'OPN-TEST-001', 'movement_type' => 'stock_opname']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'stock_opname.posted']);
    }

    public function test_stock_transfer_rejects_warehouse_from_other_business(): void
    {
        $this->seed(DatabaseSeeder::class);

        [$user, $business, $branch] = $this->context();
        $fromWarehouse = Warehouse::query()->where('business_id', $business->id)->where('branch_id', $branch->id)->firstOrFail();
        $product = Product::query()->where('business_id', $business->id)->where('sku', 'KAWI-RICE-001')->firstOrFail();
        $outsideBusiness = Business::query()->create(['uuid' => (string) Str::uuid(), 'name' => 'Outside Business']);
        $outsideWarehouse = Warehouse::query()->create([
            'business_id' => $outsideBusiness->id,
            'uuid' => (string) Str::uuid(),
            'name' => 'Outside Warehouse',
            'code' => 'OUT-WH',
        ]);

        $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Business-Id' => $business->uuid, 'X-Branch-Id' => $branch->uuid])
            ->postJson('/api/stock-transfers', [
                'from_warehouse_id' => $fromWarehouse->id,
                'to_warehouse_id' => $outsideWarehouse->id,
                'transfer_number' => 'TRF-INVALID-001',
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 1],
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['to_warehouse_id']);
    }

    public function test_production_order_consumes_ingredients_and_posts_output(): void
    {
        $this->seed(DatabaseSeeder::class);

        [$user, $business, $branch] = $this->context();
        $warehouse = Warehouse::query()->where('business_id', $business->id)->where('branch_id', $branch->id)->firstOrFail();
        $recipe = Recipe::query()->where('business_id', $business->id)->where('name', 'Recipe KAWI Rice Bowl')->with('items')->firstOrFail();
        $ingredientId = $recipe->items->first()->ingredient_product_id;
        $outputProduct = Product::query()->whereKey($recipe->product_id)->firstOrFail();
        $ingredientBefore = (float) StockBalance::query()->where('warehouse_id', $warehouse->id)->where('product_id', $ingredientId)->firstOrFail()->quantity_on_hand;
        $outputBefore = (float) StockBalance::query()->where('warehouse_id', $warehouse->id)->where('product_id', $outputProduct->id)->firstOrFail()->quantity_on_hand;

        $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Business-Id' => $business->uuid, 'X-Branch-Id' => $branch->uuid])
            ->postJson('/api/production-orders', [
                'warehouse_id' => $warehouse->id,
                'recipe_id' => $recipe->id,
                'production_number' => 'PROD-TEST-001',
                'planned_quantity' => 5,
                'actual_quantity' => 4,
            ])
            ->assertCreated()
            ->assertJsonPath('production_order.status', 'posted')
            ->assertJsonPath('production_order.waste_quantity', '1.000000');

        $ingredientAfter = (float) StockBalance::query()->where('warehouse_id', $warehouse->id)->where('product_id', $ingredientId)->firstOrFail()->quantity_on_hand;
        $outputAfter = (float) StockBalance::query()->where('warehouse_id', $warehouse->id)->where('product_id', $outputProduct->id)->firstOrFail()->quantity_on_hand;

        $this->assertSame($ingredientBefore - 5, $ingredientAfter);
        $this->assertSame($outputBefore + 4, $outputAfter);
        $this->assertDatabaseHas('stock_ledgers', ['reference_number' => 'PROD-TEST-001', 'movement_type' => 'production_consumption']);
        $this->assertDatabaseHas('stock_ledgers', ['reference_number' => 'PROD-TEST-001', 'movement_type' => 'production_output']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'production_order.posted']);
    }

    public function test_production_order_rejects_recipe_from_other_business(): void
    {
        $this->seed(DatabaseSeeder::class);

        [$user, $business, $branch] = $this->context();
        $warehouse = Warehouse::query()->where('business_id', $business->id)->where('branch_id', $branch->id)->firstOrFail();
        $outsideBusiness = Business::query()->create(['uuid' => (string) Str::uuid(), 'name' => 'Outside Production']);
        $outsideProduct = Product::query()->create([
            'business_id' => $outsideBusiness->id,
            'uuid' => (string) Str::uuid(),
            'name' => 'Outside Menu',
            'type' => 'food',
            'sku' => 'OUT-PROD-001',
        ]);
        $outsideRecipe = Recipe::query()->create([
            'business_id' => $outsideBusiness->id,
            'product_id' => $outsideProduct->id,
            'uuid' => (string) Str::uuid(),
            'name' => 'Outside Recipe',
        ]);

        $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Business-Id' => $business->uuid, 'X-Branch-Id' => $branch->uuid])
            ->postJson('/api/production-orders', [
                'warehouse_id' => $warehouse->id,
                'recipe_id' => $outsideRecipe->id,
                'production_number' => 'PROD-INVALID-001',
                'planned_quantity' => 1,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['recipe_id']);
    }

    private function context(): array
    {
        $user = User::query()->where('email', 'owner@kawi.test')->firstOrFail();
        $business = Business::query()->where('name', 'KAWI Demo Business')->firstOrFail();
        $branch = Branch::query()->where('business_id', $business->id)->where('code', 'MAIN')->firstOrFail();

        return [$user, $business, $branch];
    }
}
