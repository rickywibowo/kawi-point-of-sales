<?php

namespace Tests\Feature\Purchasing;

use App\Models\Branch;
use App\Models\Business;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\StockBalance;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PurchasingTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_and_approve_purchase_order(): void
    {
        $this->seed(DatabaseSeeder::class);
        [$user, $business, $branch, $supplier, $warehouse, $product] = $this->context();

        $response = $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Business-Id' => $business->uuid, 'X-Branch-Id' => $branch->uuid])
            ->postJson('/api/purchase-orders', [
                'supplier_id' => $supplier->id,
                'warehouse_id' => $warehouse->id,
                'po_number' => 'PO-TEST-001',
                'items' => [
                    ['product_id' => $product->id, 'quantity_ordered' => 5, 'unit_cost' => 9000, 'tax_rate' => 11],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('purchase_order.grand_total', '49950.00');

        $poId = $response->json('purchase_order.id');

        $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Business-Id' => $business->uuid, 'X-Branch-Id' => $branch->uuid])
            ->postJson("/api/purchase-orders/{$poId}/approve")
            ->assertOk()
            ->assertJsonPath('purchase_order.status', 'approved');

        $this->assertDatabaseHas('audit_logs', ['action' => 'purchase_order.approved']);
    }

    public function test_goods_receipt_posts_stock_and_supplier_payable(): void
    {
        $this->seed(DatabaseSeeder::class);
        [$user, $business, $branch, $supplier, $warehouse, $product] = $this->context();
        $before = (float) StockBalance::query()->where('warehouse_id', $warehouse->id)->where('product_id', $product->id)->firstOrFail()->quantity_on_hand;

        $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Business-Id' => $business->uuid, 'X-Branch-Id' => $branch->uuid])
            ->postJson('/api/goods-receipts', [
                'supplier_id' => $supplier->id,
                'warehouse_id' => $warehouse->id,
                'receipt_number' => 'GR-TEST-001',
                'items' => [
                    ['product_id' => $product->id, 'quantity_received' => 7, 'unit_cost' => 9000, 'tax_rate' => 11],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('goods_receipt.grand_total', '69930.00');

        $after = (float) StockBalance::query()->where('warehouse_id', $warehouse->id)->where('product_id', $product->id)->firstOrFail()->quantity_on_hand;
        $this->assertSame($before + 7, $after);
        $this->assertDatabaseHas('stock_ledgers', ['reference_number' => 'GR-TEST-001', 'movement_type' => 'purchase_receipt']);
        $this->assertDatabaseHas('supplier_payables', ['payable_number' => 'AP-GR-TEST-001', 'status' => 'open']);
    }

    public function test_purchase_order_rejects_product_from_other_business(): void
    {
        $this->seed(DatabaseSeeder::class);
        [$user, $business, $branch, $supplier, $warehouse] = $this->context();
        $outsideBusiness = Business::query()->create(['uuid' => (string) Str::uuid(), 'name' => 'Outside']);
        $outsideProduct = Product::query()->create([
            'business_id' => $outsideBusiness->id,
            'uuid' => (string) Str::uuid(),
            'name' => 'Outside Product',
            'type' => 'goods',
            'sku' => 'OUT-PUR-001',
        ]);

        $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Business-Id' => $business->uuid, 'X-Branch-Id' => $branch->uuid])
            ->postJson('/api/purchase-orders', [
                'supplier_id' => $supplier->id,
                'warehouse_id' => $warehouse->id,
                'po_number' => 'PO-INVALID-001',
                'items' => [
                    ['product_id' => $outsideProduct->id, 'quantity_ordered' => 1, 'unit_cost' => 1000],
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['items.0.product_id']);
    }

    private function context(): array
    {
        $user = User::query()->where('email', 'owner@kawi.test')->firstOrFail();
        $business = Business::query()->where('name', 'KAWI Demo Business')->firstOrFail();
        $branch = Branch::query()->where('business_id', $business->id)->where('code', 'MAIN')->firstOrFail();
        $supplier = Supplier::query()->where('business_id', $business->id)->firstOrFail();
        $warehouse = Warehouse::query()->where('business_id', $business->id)->where('branch_id', $branch->id)->firstOrFail();
        $product = Product::query()->where('business_id', $business->id)->where('sku', 'KAWI-COFFEE-001')->firstOrFail();

        return [$user, $business, $branch, $supplier, $warehouse, $product];
    }
}
