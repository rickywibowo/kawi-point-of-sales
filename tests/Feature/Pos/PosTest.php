<?php

namespace Tests\Feature\Pos;

use App\Models\Branch;
use App\Models\Business;
use App\Models\CashierShift;
use App\Models\DeliveryOrder;
use App\Models\DiningTable;
use App\Models\KitchenTicket;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Sale;
use App\Models\StockBalance;
use App\Models\StockLedger;
use App\Models\TableReservation;
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

    public function test_cashier_can_create_and_update_dining_table_status(): void
    {
        $this->seed(DatabaseSeeder::class);

        [$user, $business, $branch] = $this->context();

        $response = $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Business-Id' => $business->uuid, 'X-Branch-Id' => $branch->uuid])
            ->postJson('/api/dining-tables', [
                'code' => 'T-TEST-01',
                'name' => 'Table Test 01',
                'capacity' => 4,
                'section' => 'Test',
            ])
            ->assertCreated()
            ->assertJsonPath('dining_table.status', 'available');

        $tableId = $response->json('dining_table.id');

        $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Business-Id' => $business->uuid, 'X-Branch-Id' => $branch->uuid])
            ->patchJson("/api/dining-tables/{$tableId}/status", ['status' => 'reserved'])
            ->assertOk()
            ->assertJsonPath('dining_table.status', 'reserved');

        $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Business-Id' => $business->uuid, 'X-Branch-Id' => $branch->uuid])
            ->getJson('/api/pos')
            ->assertOk()
            ->assertJsonFragment(['code' => 'T-TEST-01']);

        $this->assertDatabaseHas('audit_logs', ['action' => 'dining_table.created']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'dining_table.status_updated']);
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

    public function test_completed_sale_creates_kitchen_ticket_and_kitchen_can_update_status(): void
    {
        $this->seed(DatabaseSeeder::class);

        [$user, $business, $branch] = $this->context();
        $shift = $this->openShift($user, $business, $branch, 'SHIFT-KDS');
        $warehouse = Warehouse::query()->where('business_id', $business->id)->where('branch_id', $branch->id)->firstOrFail();
        $product = Product::query()->where('business_id', $business->id)->where('sku', 'KAWI-RICE-001')->firstOrFail();

        $saleId = $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Business-Id' => $business->uuid, 'X-Branch-Id' => $branch->uuid])
            ->postJson('/api/sales', [
                'cashier_shift_id' => $shift->id,
                'warehouse_id' => $warehouse->id,
                'sale_number' => 'SALE-KDS-001',
                'idempotency_key' => 'sale-kds-001',
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 35000, 'notes' => 'No spicy'],
                ],
                'payments' => [
                    ['method' => 'cash', 'amount' => 77700],
                ],
            ])
            ->assertCreated()
            ->json('sale.id');

        $ticket = KitchenTicket::query()->where('sale_id', $saleId)->with('items')->firstOrFail();

        $this->assertSame('KOT-SALE-KDS-001', $ticket->ticket_number);
        $this->assertSame('open', $ticket->status);
        $this->assertSame('No spicy', $ticket->items->first()->notes);

        $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Business-Id' => $business->uuid, 'X-Branch-Id' => $branch->uuid])
            ->getJson('/api/kitchen-tickets')
            ->assertOk()
            ->assertJsonFragment(['ticket_number' => 'KOT-SALE-KDS-001']);

        $itemId = $ticket->items->first()->id;

        $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Business-Id' => $business->uuid, 'X-Branch-Id' => $branch->uuid])
            ->patchJson("/api/kitchen-ticket-items/{$itemId}/status", ['status' => 'preparing'])
            ->assertOk()
            ->assertJsonPath('kitchen_ticket_item.status', 'preparing');

        $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Business-Id' => $business->uuid, 'X-Branch-Id' => $branch->uuid])
            ->patchJson("/api/kitchen-tickets/{$ticket->id}/status", ['status' => 'served'])
            ->assertOk()
            ->assertJsonPath('kitchen_ticket.status', 'served');

        $this->assertDatabaseHas('audit_logs', ['action' => 'kitchen_ticket.created']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'kitchen_ticket_item.status_updated']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'kitchen_ticket.status_updated']);
    }

    public function test_delivery_sale_creates_delivery_order_and_can_update_status(): void
    {
        $this->seed(DatabaseSeeder::class);

        [$user, $business, $branch] = $this->context();
        $shift = $this->openShift($user, $business, $branch, 'SHIFT-DELIVERY');
        $warehouse = Warehouse::query()->where('business_id', $business->id)->where('branch_id', $branch->id)->firstOrFail();
        $product = Product::query()->where('business_id', $business->id)->where('sku', 'KAWI-RICE-001')->firstOrFail();

        $saleId = $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Business-Id' => $business->uuid, 'X-Branch-Id' => $branch->uuid])
            ->postJson('/api/sales', [
                'cashier_shift_id' => $shift->id,
                'warehouse_id' => $warehouse->id,
                'sale_number' => 'SALE-DELIVERY-001',
                'idempotency_key' => 'sale-delivery-001',
                'type' => 'delivery',
                'delivery' => [
                    'recipient_name' => 'Rina Delivery',
                    'recipient_phone' => '081200000111',
                    'address' => 'Jl. KAWI Delivery No. 1',
                    'fee' => 10000,
                ],
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 35000],
                ],
                'payments' => [
                    ['method' => 'qris', 'amount' => 48850],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('sale.type', 'delivery')
            ->assertJsonPath('sale.delivery_fee_total', '10000.00')
            ->assertJsonPath('sale.grand_total', '48850.00')
            ->json('sale.id');

        $delivery = DeliveryOrder::query()->where('sale_id', $saleId)->firstOrFail();

        $this->assertSame('DO-SALE-DELIVERY-001', $delivery->delivery_number);
        $this->assertSame('pending', $delivery->status);

        $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Business-Id' => $business->uuid, 'X-Branch-Id' => $branch->uuid])
            ->getJson('/api/delivery-orders')
            ->assertOk()
            ->assertJsonFragment(['delivery_number' => 'DO-SALE-DELIVERY-001']);

        $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Business-Id' => $business->uuid, 'X-Branch-Id' => $branch->uuid])
            ->patchJson("/api/delivery-orders/{$delivery->id}/status", [
                'status' => 'assigned',
                'courier_name' => 'Andi Courier',
                'courier_phone' => '081299900001',
            ])
            ->assertOk()
            ->assertJsonPath('delivery_order.status', 'assigned')
            ->assertJsonPath('delivery_order.courier_name', 'Andi Courier');

        $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Business-Id' => $business->uuid, 'X-Branch-Id' => $branch->uuid])
            ->getJson("/api/sales/{$saleId}/receipt")
            ->assertOk()
            ->assertJsonPath('receipt.sale.delivery.delivery_number', 'DO-SALE-DELIVERY-001')
            ->assertJsonPath('receipt.totals.delivery_fee_total', 10000);

        $this->assertDatabaseHas('audit_logs', ['action' => 'delivery_order.created']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'delivery_order.status_updated']);
    }

    public function test_delivery_sale_requires_delivery_address(): void
    {
        $this->seed(DatabaseSeeder::class);

        [$user, $business, $branch] = $this->context();
        $shift = $this->openShift($user, $business, $branch, 'SHIFT-DELIVERY-BAD');
        $warehouse = Warehouse::query()->where('business_id', $business->id)->where('branch_id', $branch->id)->firstOrFail();
        $product = Product::query()->where('business_id', $business->id)->where('sku', 'KAWI-RICE-001')->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Business-Id' => $business->uuid, 'X-Branch-Id' => $branch->uuid])
            ->postJson('/api/sales', [
                'cashier_shift_id' => $shift->id,
                'warehouse_id' => $warehouse->id,
                'sale_number' => 'SALE-DELIVERY-BAD',
                'type' => 'delivery',
                'delivery' => [
                    'recipient_name' => 'Missing Address',
                ],
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 35000],
                ],
                'payments' => [
                    ['method' => 'cash', 'amount' => 38850],
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['delivery.address']);
    }

    public function test_cashier_can_create_and_apply_promotion_to_sale(): void
    {
        $this->seed(DatabaseSeeder::class);

        [$user, $business, $branch] = $this->context();
        $shift = $this->openShift($user, $business, $branch, 'SHIFT-PROMO');
        $warehouse = Warehouse::query()->where('business_id', $business->id)->where('branch_id', $branch->id)->firstOrFail();
        $product = Product::query()->where('business_id', $business->id)->where('sku', 'KAWI-RICE-001')->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Business-Id' => $business->uuid, 'X-Branch-Id' => $branch->uuid])
            ->postJson('/api/promotions', [
                'code' => 'KAWI10',
                'name' => 'KAWI 10 Percent',
                'type' => 'percent',
                'value' => 10,
                'maximum_discount' => 5000,
            ])
            ->assertCreated()
            ->assertJsonPath('promotion.code', 'KAWI10');

        $saleId = $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Business-Id' => $business->uuid, 'X-Branch-Id' => $branch->uuid])
            ->postJson('/api/sales', [
                'cashier_shift_id' => $shift->id,
                'warehouse_id' => $warehouse->id,
                'sale_number' => 'SALE-PROMO-001',
                'idempotency_key' => 'sale-promo-001',
                'promotion_code' => 'KAWI10',
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 35000],
                ],
                'payments' => [
                    ['method' => 'cash', 'amount' => 35350],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('sale.promotion_code', 'KAWI10')
            ->assertJsonPath('sale.promotion_discount_total', '3500.00')
            ->assertJsonPath('sale.grand_total', '35350.00')
            ->json('sale.id');

        $this->assertSame(1, Promotion::query()->where('code', 'KAWI10')->value('usage_count'));

        $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Business-Id' => $business->uuid, 'X-Branch-Id' => $branch->uuid])
            ->getJson("/api/sales/{$saleId}/receipt")
            ->assertOk()
            ->assertJsonPath('receipt.sale.promotion_code', 'KAWI10')
            ->assertJsonPath('receipt.totals.promotion_discount_total', 3500);

        $this->assertDatabaseHas('audit_logs', ['action' => 'promotion.created']);
    }

    public function test_promotion_rejects_sale_below_minimum_subtotal(): void
    {
        $this->seed(DatabaseSeeder::class);

        [$user, $business, $branch] = $this->context();
        $shift = $this->openShift($user, $business, $branch, 'SHIFT-PROMO-MIN');
        $warehouse = Warehouse::query()->where('business_id', $business->id)->where('branch_id', $branch->id)->firstOrFail();
        $product = Product::query()->where('business_id', $business->id)->where('sku', 'KAWI-RICE-001')->firstOrFail();

        Promotion::query()->create([
            'business_id' => $business->id,
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'code' => 'MIN100',
            'name' => 'Minimum 100K',
            'type' => 'fixed',
            'value' => 10000,
            'minimum_subtotal' => 100000,
        ]);

        $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Business-Id' => $business->uuid, 'X-Branch-Id' => $branch->uuid])
            ->postJson('/api/sales', [
                'cashier_shift_id' => $shift->id,
                'warehouse_id' => $warehouse->id,
                'sale_number' => 'SALE-PROMO-MIN',
                'promotion_code' => 'MIN100',
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 35000],
                ],
                'payments' => [
                    ['method' => 'cash', 'amount' => 38850],
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['promotion_code']);
    }

    public function test_dine_in_sale_requires_table_and_marks_table_cleaning(): void
    {
        $this->seed(DatabaseSeeder::class);

        [$user, $business, $branch] = $this->context();
        $shift = $this->openShift($user, $business, $branch, 'SHIFT-DINE-IN');
        $warehouse = Warehouse::query()->where('business_id', $business->id)->where('branch_id', $branch->id)->firstOrFail();
        $product = Product::query()->where('business_id', $business->id)->where('sku', 'KAWI-RICE-001')->firstOrFail();
        $table = DiningTable::query()->create([
            'business_id' => $business->id,
            'branch_id' => $branch->id,
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'code' => 'DINE-01',
            'name' => 'Dine Table 01',
            'capacity' => 2,
            'status' => 'available',
        ]);

        $saleId = $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Business-Id' => $business->uuid, 'X-Branch-Id' => $branch->uuid])
            ->postJson('/api/sales', [
                'cashier_shift_id' => $shift->id,
                'warehouse_id' => $warehouse->id,
                'dining_table_id' => $table->id,
                'sale_number' => 'SALE-DINE-001',
                'idempotency_key' => 'sale-dine-001',
                'type' => 'dine_in',
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 35000],
                ],
                'payments' => [
                    ['method' => 'cash', 'amount' => 38850],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('sale.dining_table_id', $table->id)
            ->json('sale.id');

        $this->assertSame('cleaning', $table->fresh()->status);

        $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Business-Id' => $business->uuid, 'X-Branch-Id' => $branch->uuid])
            ->getJson("/api/sales/{$saleId}/receipt")
            ->assertOk()
            ->assertJsonPath('receipt.sale.dining_table.code', 'DINE-01');
    }

    public function test_dine_in_sale_without_table_is_rejected(): void
    {
        $this->seed(DatabaseSeeder::class);

        [$user, $business, $branch] = $this->context();
        $shift = $this->openShift($user, $business, $branch, 'SHIFT-DINE-BAD');
        $warehouse = Warehouse::query()->where('business_id', $business->id)->where('branch_id', $branch->id)->firstOrFail();
        $product = Product::query()->where('business_id', $business->id)->where('sku', 'KAWI-RICE-001')->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Business-Id' => $business->uuid, 'X-Branch-Id' => $branch->uuid])
            ->postJson('/api/sales', [
                'cashier_shift_id' => $shift->id,
                'warehouse_id' => $warehouse->id,
                'sale_number' => 'SALE-DINE-BAD',
                'type' => 'dine_in',
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 35000],
                ],
                'payments' => [
                    ['method' => 'cash', 'amount' => 38850],
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['dining_table_id']);
    }

    public function test_cashier_can_create_and_seat_table_reservation(): void
    {
        $this->seed(DatabaseSeeder::class);

        [$user, $business, $branch] = $this->context();
        $table = DiningTable::query()->create([
            'business_id' => $business->id,
            'branch_id' => $branch->id,
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'code' => 'RSV-01',
            'name' => 'Reservation Table 01',
            'capacity' => 4,
            'status' => 'available',
        ]);

        $reservationId = $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Business-Id' => $business->uuid, 'X-Branch-Id' => $branch->uuid])
            ->postJson("/api/dining-tables/{$table->id}/reservations", [
                'reservation_number' => 'RSV-TEST-001',
                'guest_name' => 'Rina Reservation',
                'guest_phone' => '081200000099',
                'party_size' => 3,
                'reserved_at' => now()->addHour()->toIso8601String(),
            ])
            ->assertCreated()
            ->assertJsonPath('table_reservation.status', 'booked')
            ->json('table_reservation.id');

        $this->assertSame('reserved', $table->fresh()->status);

        $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Business-Id' => $business->uuid, 'X-Branch-Id' => $branch->uuid])
            ->patchJson("/api/table-reservations/{$reservationId}/seat")
            ->assertOk()
            ->assertJsonPath('table_reservation.status', 'seated');

        $this->assertSame('occupied', $table->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'table_reservation.created']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'table_reservation.seated']);
    }

    public function test_cashier_can_cancel_table_reservation_and_release_table(): void
    {
        $this->seed(DatabaseSeeder::class);

        [$user, $business, $branch] = $this->context();
        $table = DiningTable::query()->create([
            'business_id' => $business->id,
            'branch_id' => $branch->id,
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'code' => 'RSV-02',
            'name' => 'Reservation Table 02',
            'capacity' => 2,
            'status' => 'available',
        ]);

        $reservation = TableReservation::query()->create([
            'business_id' => $business->id,
            'branch_id' => $branch->id,
            'dining_table_id' => $table->id,
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'reservation_number' => 'RSV-CANCEL-001',
            'guest_name' => 'Cancel Guest',
            'party_size' => 2,
            'reserved_at' => now()->addHour(),
            'status' => 'booked',
        ]);
        $table->update(['status' => 'reserved']);

        $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Business-Id' => $business->uuid, 'X-Branch-Id' => $branch->uuid])
            ->patchJson("/api/table-reservations/{$reservation->id}/cancel")
            ->assertOk()
            ->assertJsonPath('table_reservation.status', 'cancelled');

        $this->assertSame('available', $table->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'table_reservation.cancelled']);
    }

    public function test_table_reservation_rejects_overlapping_booking(): void
    {
        $this->seed(DatabaseSeeder::class);

        [$user, $business, $branch] = $this->context();
        $table = DiningTable::query()->create([
            'business_id' => $business->id,
            'branch_id' => $branch->id,
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'code' => 'RSV-03',
            'name' => 'Reservation Table 03',
            'capacity' => 4,
            'status' => 'reserved',
        ]);
        $reservedAt = now()->addHour();

        TableReservation::query()->create([
            'business_id' => $business->id,
            'branch_id' => $branch->id,
            'dining_table_id' => $table->id,
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'reservation_number' => 'RSV-EXISTING-001',
            'guest_name' => 'Existing Guest',
            'party_size' => 2,
            'reserved_at' => $reservedAt,
            'status' => 'booked',
        ]);

        $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Business-Id' => $business->uuid, 'X-Branch-Id' => $branch->uuid])
            ->postJson("/api/dining-tables/{$table->id}/reservations", [
                'reservation_number' => 'RSV-OVERLAP-001',
                'guest_name' => 'Overlap Guest',
                'party_size' => 2,
                'reserved_at' => $reservedAt->copy()->addMinutes(30)->toIso8601String(),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['reserved_at']);
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
