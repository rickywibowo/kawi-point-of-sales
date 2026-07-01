<?php

namespace Tests\Feature\Inventory;

use App\Models\Branch;
use App\Models\Business;
use App\Models\Product;
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
}
