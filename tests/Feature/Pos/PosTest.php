<?php

namespace Tests\Feature\Pos;

use App\Models\Branch;
use App\Models\Business;
use App\Models\CashierShift;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockBalance;
use App\Models\StockLedger;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_can_open_shift(): void
    {
        $this->seed(DatabaseSeeder::class);

        [$user, $business, $branch] = $this->context();

        $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Business-Id' => $business->uuid, 'X-Branch-Id' => $branch->uuid])
            ->postJson('/api/cashier-shifts', [
                'shift_number' => 'SHIFT-TEST-001',
                'opening_cash' => 200000,
            ])
            ->assertCreated()
            ->assertJsonPath('shift.status', 'open');

        $this->assertDatabaseHas('audit_logs', ['action' => 'cashier_shift.opened']);
    }

    public function test_cashier_can_hold_transaction(): void
    {
        $this->seed(DatabaseSeeder::class);

        [$user, $business, $branch] = $this->context();
        $product = Product::query()->where('business_id', $business->id)->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Business-Id' => $business->uuid, 'X-Branch-Id' => $branch->uuid])
            ->postJson('/api/held-transactions', [
                'hold_number' => 'HOLD-TEST-001',
                'payload' => [
                    'items' => [
                        ['product_id' => $product->id, 'quantity' => 1],
                    ],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('held_transaction.hold_number', 'HOLD-TEST-001');

        $this->assertDatabaseHas('audit_logs', ['action' => 'sale.held']);
    }

    public function test_complete_sale_creates_items_payments_and_stock_consumption(): void
    {
        $this->seed(DatabaseSeeder::class);

        [$user, $business, $branch] = $this->context();
        $shift = $this->openShift($user, $business, $branch);
        $warehouse = Warehouse::query()->where('business_id', $business->id)->where('branch_id', $branch->id)->firstOrFail();
        $product = Product::query()->where('business_id', $business->id)->where('sku', 'KAWI-RICE-001')->firstOrFail();
        $before = (float) StockBalance::query()->where('warehouse_id', $warehouse->id)->where('product_id', $product->id)->firstOrFail()->quantity_on_hand;

        $response = $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Business-Id' => $business->uuid, 'X-Branch-Id' => $branch->uuid])
            ->postJson('/api/sales', [
                'cashier_shift_id' => $shift->id,
                'warehouse_id' => $warehouse->id,
                'sale_number' => 'SALE-TEST-001',
                'idempotency_key' => 'offline-sale-001',
                'type' => 'takeaway',
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 35000],
                ],
                'payments' => [
                    ['method' => 'cash', 'amount' => 77700],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('sale.sale_number', 'SALE-TEST-001');

        $saleId = $response->json('sale.id');
        $this->assertDatabaseHas('sale_items', ['sale_id' => $saleId, 'product_id' => $product->id]);
        $this->assertDatabaseHas('sale_payments', ['sale_id' => $saleId, 'method' => 'cash']);
        $this->assertDatabaseHas('stock_ledgers', ['reference_number' => 'SALE-TEST-001', 'movement_type' => 'sales_consumption']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'sale.completed']);

        $after = (float) StockBalance::query()->where('warehouse_id', $warehouse->id)->where('product_id', $product->id)->firstOrFail()->quantity_on_hand;
        $this->assertSame($before - 2, $after);
    }

    public function test_sale_idempotency_key_prevents_duplicate_sales(): void
    {
        $this->seed(DatabaseSeeder::class);

        [$user, $business, $branch] = $this->context();
        $shift = $this->openShift($user, $business, $branch);
        $warehouse = Warehouse::query()->where('business_id', $business->id)->where('branch_id', $branch->id)->firstOrFail();
        $product = Product::query()->where('business_id', $business->id)->where('sku', 'KAWI-COFFEE-001')->firstOrFail();

        $payload = [
            'cashier_shift_id' => $shift->id,
            'warehouse_id' => $warehouse->id,
            'sale_number' => 'SALE-TEST-002',
            'idempotency_key' => 'offline-sale-002',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 25000],
            ],
            'payments' => [
                ['method' => 'qris', 'amount' => 27750],
            ],
        ];

        $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Business-Id' => $business->uuid, 'X-Branch-Id' => $branch->uuid])
            ->postJson('/api/sales', $payload)
            ->assertCreated();

        $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Business-Id' => $business->uuid, 'X-Branch-Id' => $branch->uuid])
            ->postJson('/api/sales', $payload)
            ->assertCreated();

        $this->assertSame(1, Sale::query()->where('idempotency_key', 'offline-sale-002')->count());
        $this->assertSame(1, StockLedger::query()->where('reference_number', 'SALE-TEST-002')->count());
    }

    public function test_cashier_can_close_shift_after_cash_sale(): void
    {
        $this->seed(DatabaseSeeder::class);

        [$user, $business, $branch] = $this->context();
        $shift = $this->openShift($user, $business, $branch, 'SHIFT-TEST-004');

        $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Business-Id' => $business->uuid, 'X-Branch-Id' => $branch->uuid])
            ->postJson("/api/cashier-shifts/{$shift->id}/close", [
                'actual_cash' => 200000,
            ])
            ->assertOk()
            ->assertJsonPath('shift.status', 'closed');

        $this->assertDatabaseHas('audit_logs', ['action' => 'cashier_shift.closed']);
    }

    private function context(): array
    {
        $user = User::query()->where('email', 'owner@kawi.test')->firstOrFail();
        $business = Business::query()->where('name', 'KAWI Demo Business')->firstOrFail();
        $branch = Branch::query()->where('business_id', $business->id)->where('code', 'MAIN')->firstOrFail();

        return [$user, $business, $branch];
    }

    private function openShift(User $user, Business $business, Branch $branch, string $number = 'SHIFT-TEST-SALE'): CashierShift
    {
        return CashierShift::query()->create([
            'business_id' => $business->id,
            'branch_id' => $branch->id,
            'user_id' => $user->id,
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'shift_number' => $number,
            'opening_cash' => 200000,
            'expected_cash' => 200000,
            'status' => 'open',
            'opened_at' => now(),
        ]);
    }
}
