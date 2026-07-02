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

    public function test_cashier_can_record_cash_movement_and_close_shift_with_adjusted_expected_cash(): void
    {
        $this->seed(DatabaseSeeder::class);

        [$user, $business, $branch] = $this->context();
        $shift = $this->openShift($user, $business, $branch, 'SHIFT-CASH-MOVE');

        $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Business-Id' => $business->uuid, 'X-Branch-Id' => $branch->uuid])
            ->postJson("/api/cashier-shifts/{$shift->id}/cash-movements", [
                'type' => 'cash_in',
                'amount' => 50000,
                'reason' => 'Tambahan kas kecil',
            ])
            ->assertCreated()
            ->assertJsonPath('cash_movement.type', 'cash_in');

        $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Business-Id' => $business->uuid, 'X-Branch-Id' => $branch->uuid])
            ->postJson("/api/cashier-shifts/{$shift->id}/close", [
                'actual_cash' => 250000,
            ])
            ->assertOk()
            ->assertJsonPath('shift.expected_cash', '250000.00')
            ->assertJsonPath('shift.cash_difference', '0.00');

        $this->assertDatabaseHas('audit_logs', ['action' => 'cash_movement.created']);
    }

    public function test_manager_can_void_sale_and_restore_stock(): void
    {
        $this->seed(DatabaseSeeder::class);

        [$user, $business, $branch] = $this->context();
        [$sale, $warehouse, $product, $before] = $this->createCompletedSale($user, $business, $branch, 'SALE-VOID-001', 'void-sale-001');

        $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Business-Id' => $business->uuid, 'X-Branch-Id' => $branch->uuid])
            ->postJson("/api/sales/{$sale->id}/void", [
                'reason' => 'Salah input',
            ])
            ->assertOk()
            ->assertJsonPath('sale.status', 'voided');

        $after = (float) StockBalance::query()->where('warehouse_id', $warehouse->id)->where('product_id', $product->id)->firstOrFail()->quantity_on_hand;

        $this->assertSame($before, $after);
        $this->assertDatabaseHas('stock_ledgers', ['reference_number' => 'SALE-VOID-001', 'movement_type' => 'sales_void']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'sale.voided']);
    }

    public function test_manager_can_refund_sale_and_prevent_second_status_change(): void
    {
        $this->seed(DatabaseSeeder::class);

        [$user, $business, $branch] = $this->context();
        [$sale] = $this->createCompletedSale($user, $business, $branch, 'SALE-REFUND-001', 'refund-sale-001');

        $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Business-Id' => $business->uuid, 'X-Branch-Id' => $branch->uuid])
            ->postJson("/api/sales/{$sale->id}/refund", [
                'reason' => 'Customer return',
            ])
            ->assertOk()
            ->assertJsonPath('sale.status', 'refunded');

        $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Business-Id' => $business->uuid, 'X-Branch-Id' => $branch->uuid])
            ->postJson("/api/sales/{$sale->id}/void")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['sale']);

        $this->assertDatabaseHas('stock_ledgers', ['reference_number' => 'SALE-REFUND-001', 'movement_type' => 'sales_refund']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'sale.refunded']);
    }

    public function test_cashier_can_view_digital_receipt_for_completed_sale(): void
    {
        $this->seed(DatabaseSeeder::class);

        [$user, $business, $branch] = $this->context();
        [$sale] = $this->createCompletedSale($user, $business, $branch, 'SALE-RECEIPT-001', 'receipt-sale-001');

        $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Business-Id' => $business->uuid, 'X-Branch-Id' => $branch->uuid])
            ->getJson("/api/sales/{$sale->id}/receipt")
            ->assertOk()
            ->assertJsonPath('receipt.sale.sale_number', 'SALE-RECEIPT-001')
            ->assertJsonPath('receipt.business.name', 'KAWI Demo Business')
            ->assertJsonPath('receipt.branch.code', 'MAIN')
            ->assertJsonPath('receipt.items.0.name', 'KAWI Rice Bowl')
            ->assertJsonPath('receipt.payments.0.method', 'cash')
            ->assertJsonPath('receipt.digital.qr_payload', 'KAWI-POS:'.$sale->uuid)
            ->assertJsonStructure([
                'receipt' => [
                    'business',
                    'branch',
                    'sale',
                    'items',
                    'payments',
                    'totals',
                    'digital',
                ],
            ]);
    }

    public function test_receipt_endpoint_rejects_sale_outside_active_branch(): void
    {
        $this->seed(DatabaseSeeder::class);

        [$user, $business, $branch] = $this->context();
        [$sale] = $this->createCompletedSale($user, $business, $branch, 'SALE-RECEIPT-OUT', 'receipt-sale-out');
        $outsideBranch = Branch::query()->create([
            'business_id' => $business->id,
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'name' => 'Second Branch',
            'code' => 'SECOND',
        ]);

        $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Business-Id' => $business->uuid, 'X-Branch-Id' => $outsideBranch->uuid])
            ->getJson("/api/sales/{$sale->id}/receipt")
            ->assertForbidden();
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

    private function createCompletedSale(User $user, Business $business, Branch $branch, string $saleNumber, string $idempotencyKey): array
    {
        $shift = $this->openShift($user, $business, $branch, 'SHIFT-'.$saleNumber);
        $warehouse = Warehouse::query()->where('business_id', $business->id)->where('branch_id', $branch->id)->firstOrFail();
        $product = Product::query()->where('business_id', $business->id)->where('sku', 'KAWI-RICE-001')->firstOrFail();
        $before = (float) StockBalance::query()->where('warehouse_id', $warehouse->id)->where('product_id', $product->id)->firstOrFail()->quantity_on_hand;

        $saleId = $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Business-Id' => $business->uuid, 'X-Branch-Id' => $branch->uuid])
            ->postJson('/api/sales', [
                'cashier_shift_id' => $shift->id,
                'warehouse_id' => $warehouse->id,
                'sale_number' => $saleNumber,
                'idempotency_key' => $idempotencyKey,
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 35000],
                ],
                'payments' => [
                    ['method' => 'cash', 'amount' => 77700],
                ],
            ])
            ->assertCreated()
            ->json('sale.id');

        return [
            Sale::query()->findOrFail($saleId),
            $warehouse,
            $product,
            $before,
        ];
    }
}
