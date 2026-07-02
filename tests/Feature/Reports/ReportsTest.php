<?php

namespace Tests\Feature\Reports;

use App\Models\Branch;
use App\Models\Business;
use App\Models\CashierShift;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_reports_endpoint_returns_main_sections(): void
    {
        $this->seed(DatabaseSeeder::class);
        [$user, $business, $branch] = $this->context();

        $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Business-Id' => $business->uuid, 'X-Branch-Id' => $branch->uuid])
            ->getJson('/api/reports')
            ->assertOk()
            ->assertJsonStructure([
                'reports' => [
                    'period',
                    'sales',
                    'sales_by_branch',
                    'sales_by_product',
                    'payment_methods',
                    'stock',
                    'stock_movements',
                    'purchasing',
                    'accounting',
                ],
            ]);
    }

    public function test_reports_include_sales_stock_purchasing_and_accounting_numbers(): void
    {
        $this->seed(DatabaseSeeder::class);
        [$user, $business, $branch] = $this->context();
        $warehouse = Warehouse::query()->where('business_id', $business->id)->where('branch_id', $branch->id)->firstOrFail();
        $product = Product::query()->where('business_id', $business->id)->where('sku', 'KAWI-RICE-001')->firstOrFail();
        $supplier = Supplier::query()->where('business_id', $business->id)->firstOrFail();
        $shift = $this->openShift($user, $business, $branch);

        $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Business-Id' => $business->uuid, 'X-Branch-Id' => $branch->uuid])
            ->postJson('/api/sales', [
                'cashier_shift_id' => $shift->id,
                'warehouse_id' => $warehouse->id,
                'sale_number' => 'SALE-REPORT-001',
                'idempotency_key' => 'report-sale-001',
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 35000],
                ],
                'payments' => [
                    ['method' => 'cash', 'amount' => 38850],
                ],
            ])
            ->assertCreated();

        $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Business-Id' => $business->uuid, 'X-Branch-Id' => $branch->uuid])
            ->postJson('/api/goods-receipts', [
                'supplier_id' => $supplier->id,
                'warehouse_id' => $warehouse->id,
                'receipt_number' => 'GR-REPORT-001',
                'items' => [
                    ['product_id' => $product->id, 'quantity_received' => 2, 'unit_cost' => 18000, 'tax_rate' => 11],
                ],
            ])
            ->assertCreated();

        $reports = $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Business-Id' => $business->uuid, 'X-Branch-Id' => $branch->uuid])
            ->getJson('/api/reports?date_from=1970-01-01&date_to=2100-01-01')
            ->assertOk()
            ->json('reports');

        $this->assertGreaterThanOrEqual(1, $reports['sales']['transaction_count']);
        $this->assertGreaterThan(0, (float) $reports['sales']['grand_total']);
        $this->assertGreaterThan(0, (float) $reports['stock']['stock_value']);
        $this->assertGreaterThan(0, (float) $reports['purchasing']['goods_receipt_total']);
        $this->assertArrayHasKey('profit_and_loss', $reports['accounting']);
    }

    public function test_reports_date_filter_can_exclude_transactions(): void
    {
        $this->seed(DatabaseSeeder::class);
        [$user, $business, $branch] = $this->context();

        $reports = $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Business-Id' => $business->uuid, 'X-Branch-Id' => $branch->uuid])
            ->getJson('/api/reports?date_from=2000-01-01&date_to=2000-01-31')
            ->assertOk()
            ->json('reports');

        $this->assertSame(0, $reports['sales']['transaction_count']);
    }

    private function context(): array
    {
        $user = User::query()->where('email', 'owner@kawi.test')->firstOrFail();
        $business = Business::query()->where('name', 'KAWI Demo Business')->firstOrFail();
        $branch = Branch::query()->where('business_id', $business->id)->where('code', 'MAIN')->firstOrFail();

        return [$user, $business, $branch];
    }

    private function openShift(User $user, Business $business, Branch $branch): CashierShift
    {
        return CashierShift::query()->create([
            'business_id' => $business->id,
            'branch_id' => $branch->id,
            'user_id' => $user->id,
            'uuid' => (string) Str::uuid(),
            'shift_number' => 'SHIFT-REPORT-001',
            'opening_cash' => 100000,
            'expected_cash' => 100000,
            'status' => 'open',
            'opened_at' => now(),
        ]);
    }
}
