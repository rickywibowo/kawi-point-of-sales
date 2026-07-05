<?php

namespace Tests\Feature\MasterData;

use App\Models\Branch;
use App\Models\Business;
use App\Models\Category;
use App\Models\Product;
use App\Models\Tax;
use App\Models\UnitOfMeasure;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class MasterDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_master_data_index_is_scoped_to_active_business(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::query()->where('email', 'owner@kawi.test')->firstOrFail();
        $business = Business::query()->where('name', 'KAWI Demo Business')->firstOrFail();
        $outsideBusiness = Business::query()->create(['uuid' => (string) Str::uuid(), 'name' => 'Outside Business']);

        Product::query()->create([
            'business_id' => $outsideBusiness->id,
            'uuid' => (string) Str::uuid(),
            'name' => 'Outside Product',
            'type' => 'goods',
            'sku' => 'OUT-001',
            'base_price' => 1000,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->withHeader('X-Business-Id', $business->uuid)
            ->getJson('/api/master-data');

        $response->assertOk();
        $this->assertStringContainsString('KAWI Rice Bowl', $response->getContent());
        $this->assertStringNotContainsString('Outside Product', $response->getContent());
    }

    public function test_inventory_staff_can_create_category_and_product_in_business_scope(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::query()->where('email', 'owner@kawi.test')->firstOrFail();
        $business = Business::query()->where('name', 'KAWI Demo Business')->firstOrFail();
        $branch = Branch::query()->where('business_id', $business->id)->firstOrFail();
        $unit = UnitOfMeasure::query()->where('business_id', $business->id)->firstOrFail();
        $tax = Tax::query()->where('business_id', $business->id)->firstOrFail();

        $category = $this->actingAs($user, 'sanctum')
            ->withHeader('X-Business-Id', $business->uuid)
            ->postJson('/api/categories', ['name' => 'Snack'])
            ->assertCreated()
            ->json('category');

        $this->actingAs($user, 'sanctum')
            ->withHeader('X-Business-Id', $business->uuid)
            ->postJson('/api/products', [
                'category_id' => $category['id'],
                'unit_of_measure_id' => $unit->id,
                'tax_id' => $tax->id,
                'name' => 'KAWI Snack Pack',
                'type' => 'goods',
                'sku' => 'KAWI-SNACK-001',
                'base_price' => 15000,
                'cost_price' => 7000,
                'branch_prices' => [
                    ['branch_id' => $branch->id, 'price' => 15000],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('product.name', 'KAWI Snack Pack');

        $this->assertDatabaseHas('products', [
            'business_id' => $business->id,
            'sku' => 'KAWI-SNACK-001',
        ]);

        $this->assertDatabaseHas('audit_logs', ['action' => 'product.created']);
    }

    public function test_inventory_staff_can_delete_empty_category(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::query()->where('email', 'owner@kawi.test')->firstOrFail();
        $business = Business::query()->where('name', 'KAWI Demo Business')->firstOrFail();

        $category = $this->actingAs($user, 'sanctum')
            ->withHeader('X-Business-Id', $business->uuid)
            ->postJson('/api/categories', ['name' => 'Temporary Category'])
            ->assertCreated()
            ->json('category');

        $this->actingAs($user, 'sanctum')
            ->withHeader('X-Business-Id', $business->uuid)
            ->deleteJson('/api/categories/'.$category['id'])
            ->assertNoContent();

        $this->assertDatabaseMissing('categories', ['id' => $category['id']]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'category.deleted']);
    }

    public function test_category_with_products_cannot_be_deleted(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::query()->where('email', 'owner@kawi.test')->firstOrFail();
        $business = Business::query()->where('name', 'KAWI Demo Business')->firstOrFail();
        $category = Category::query()
            ->where('business_id', $business->id)
            ->whereHas('products')
            ->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->withHeader('X-Business-Id', $business->uuid)
            ->deleteJson('/api/categories/'.$category->id)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['category_id']);

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_product_creation_rejects_branch_from_other_business(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::query()->where('email', 'owner@kawi.test')->firstOrFail();
        $business = Business::query()->where('name', 'KAWI Demo Business')->firstOrFail();
        $category = Category::query()->where('business_id', $business->id)->firstOrFail();
        $outsideBusiness = Business::query()->create(['uuid' => (string) Str::uuid(), 'name' => 'Outside Business']);
        $outsideBranch = Branch::query()->create([
            'business_id' => $outsideBusiness->id,
            'uuid' => (string) Str::uuid(),
            'name' => 'Outside Branch',
            'code' => 'OUT',
        ]);

        $this->actingAs($user, 'sanctum')
            ->withHeader('X-Business-Id', $business->uuid)
            ->postJson('/api/products', [
                'category_id' => $category->id,
                'name' => 'Invalid Branch Price Product',
                'type' => 'goods',
                'sku' => 'INVALID-BRANCH',
                'base_price' => 10000,
                'branch_prices' => [
                    ['branch_id' => $outsideBranch->id, 'price' => 10000],
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['branch_prices.0.branch_id']);
    }
}
