<?php

namespace Tests\Feature\Offline;

use App\Models\Branch;
use App\Models\Business;
use App\Models\CashierShift;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OfflineSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_offline_sales_sync_creates_sale(): void
    {
        $this->seed(DatabaseSeeder::class);
        [$user, $business, $branch, $shift, $warehouse, $product] = $this->context();

        $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Business-Id' => $business->uuid, 'X-Branch-Id' => $branch->uuid])
            ->postJson('/api/offline/sales/sync', [
                'batch_key' => 'BATCH-TEST-001',
                'sales' => [
                    [
                        'client_uuid' => 'client-sale-001',
                        'payload' => $this->salePayload($shift->id, $warehouse->id, $product->id, 'OFF-SALE-001', 'client-sale-001'),
                    ],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('batch.synced_count', 1)
            ->assertJsonPath('batch.conflict_count', 0);

        $this->assertDatabaseHas('sales', ['sale_number' => 'OFF-SALE-001', 'idempotency_key' => 'client-sale-001']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'offline_sales.synced']);
    }

    public function test_offline_sales_sync_is_idempotent(): void
    {
        $this->seed(DatabaseSeeder::class);
        [$user, $business, $branch, $shift, $warehouse, $product] = $this->context();
        $payload = [
            'batch_key' => 'BATCH-TEST-002',
            'sales' => [
                [
                    'client_uuid' => 'client-sale-002',
                    'payload' => $this->salePayload($shift->id, $warehouse->id, $product->id, 'OFF-SALE-002', 'client-sale-002'),
                ],
            ],
        ];

        $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Business-Id' => $business->uuid, 'X-Branch-Id' => $branch->uuid])
            ->postJson('/api/offline/sales/sync', $payload)
            ->assertOk();

        $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Business-Id' => $business->uuid, 'X-Branch-Id' => $branch->uuid])
            ->postJson('/api/offline/sales/sync', $payload)
            ->assertOk();

        $this->assertSame(1, Sale::query()->where('idempotency_key', 'client-sale-002')->count());
    }

    public function test_invalid_offline_sale_becomes_conflict(): void
    {
        $this->seed(DatabaseSeeder::class);
        [$user, $business, $branch] = $this->context();

        $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Business-Id' => $business->uuid, 'X-Branch-Id' => $branch->uuid])
            ->postJson('/api/offline/sales/sync', [
                'batch_key' => 'BATCH-TEST-003',
                'sales' => [
                    [
                        'client_uuid' => 'client-sale-conflict',
                        'payload' => [
                            'sale_number' => 'OFF-CONFLICT-001',
                            'idempotency_key' => 'client-sale-conflict',
                            'cashier_shift_id' => 999999,
                            'warehouse_id' => 999999,
                            'items' => [],
                            'payments' => [],
                        ],
                    ],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('batch.synced_count', 0)
            ->assertJsonPath('batch.conflict_count', 1);

        $this->assertDatabaseHas('offline_sync_conflicts', ['client_uuid' => 'client-sale-conflict', 'status' => 'open']);
    }

    public function test_conflict_endpoint_returns_open_conflicts(): void
    {
        $this->seed(DatabaseSeeder::class);
        [$user, $business, $branch] = $this->context();

        $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Business-Id' => $business->uuid, 'X-Branch-Id' => $branch->uuid])
            ->getJson('/api/offline/conflicts')
            ->assertOk()
            ->assertJsonStructure(['conflicts']);
    }

    private function context(): array
    {
        $user = User::query()->where('email', 'owner@kawi.test')->firstOrFail();
        $business = Business::query()->where('name', 'KAWI Demo Business')->firstOrFail();
        $branch = Branch::query()->where('business_id', $business->id)->where('code', 'MAIN')->firstOrFail();
        $shift = CashierShift::query()->create([
            'business_id' => $business->id,
            'branch_id' => $branch->id,
            'user_id' => $user->id,
            'uuid' => (string) Str::uuid(),
            'shift_number' => 'SHIFT-OFFLINE-'.Str::random(6),
            'opening_cash' => 100000,
            'expected_cash' => 100000,
            'status' => 'open',
            'opened_at' => now(),
        ]);
        $warehouse = Warehouse::query()->where('business_id', $business->id)->where('branch_id', $branch->id)->firstOrFail();
        $product = Product::query()->where('business_id', $business->id)->where('sku', 'KAWI-RICE-001')->firstOrFail();

        return [$user, $business, $branch, $shift, $warehouse, $product];
    }

    private function salePayload(int $shiftId, int $warehouseId, int $productId, string $saleNumber, string $idempotencyKey): array
    {
        return [
            'cashier_shift_id' => $shiftId,
            'warehouse_id' => $warehouseId,
            'sale_number' => $saleNumber,
            'idempotency_key' => $idempotencyKey,
            'type' => 'takeaway',
            'items' => [
                ['product_id' => $productId, 'quantity' => 1, 'unit_price' => 35000],
            ],
            'payments' => [
                ['method' => 'cash', 'amount' => 38850],
            ],
        ];
    }
}
