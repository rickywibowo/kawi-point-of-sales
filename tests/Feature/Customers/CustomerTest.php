<?php

namespace Tests\Feature\Customers;

use App\Models\Branch;
use App\Models\Business;
use App\Models\CashierShift;
use App\Models\Customer;
use App\Models\CustomerLoyaltyTransaction;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CustomerTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_can_create_search_and_update_customer(): void
    {
        $this->seed(DatabaseSeeder::class);
        [$user, $business] = $this->context();

        $customer = $this->actingAs($user, 'sanctum')
            ->withHeader('X-Business-Id', $business->uuid)
            ->postJson('/api/customers', [
                'name' => 'Rina Member',
                'phone' => '081200000001',
                'email' => 'rina@example.test',
                'address' => 'Makassar',
                'loyalty_points' => 25,
            ])
            ->assertCreated()
            ->assertJsonPath('customer.name', 'Rina Member')
            ->json('customer');

        $this->actingAs($user, 'sanctum')
            ->withHeader('X-Business-Id', $business->uuid)
            ->getJson('/api/customers?search=Rina')
            ->assertOk()
            ->assertJsonPath('customers.data.0.name', 'Rina Member');

        $this->actingAs($user, 'sanctum')
            ->withHeader('X-Business-Id', $business->uuid)
            ->patchJson('/api/customers/'.$customer['id'], [
                'notes' => 'Prefer QRIS receipt',
                'loyalty_points' => 30,
            ])
            ->assertOk()
            ->assertJsonPath('customer.loyalty_points', 30);

        $this->assertDatabaseHas('audit_logs', ['action' => 'customer.created']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'customer.updated']);
    }

    public function test_customer_profile_includes_sales_summary_and_recent_sales(): void
    {
        $this->seed(DatabaseSeeder::class);
        [$user, $business, $branch] = $this->context();
        $customer = Customer::query()->where('business_id', $business->id)->firstOrFail();
        $warehouse = Warehouse::query()->where('business_id', $business->id)->where('branch_id', $branch->id)->firstOrFail();
        $product = Product::query()->where('business_id', $business->id)->where('sku', 'KAWI-RICE-001')->firstOrFail();
        $shift = $this->openShift($user, $business, $branch);

        $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Business-Id' => $business->uuid, 'X-Branch-Id' => $branch->uuid])
            ->postJson('/api/sales', [
                'cashier_shift_id' => $shift->id,
                'warehouse_id' => $warehouse->id,
                'customer_id' => $customer->id,
                'sale_number' => 'SALE-CUSTOMER-001',
                'idempotency_key' => 'customer-sale-001',
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 35000],
                ],
                'payments' => [
                    ['method' => 'cash', 'amount' => 38850],
                ],
            ])
            ->assertCreated();

        $profile = $this->actingAs($user, 'sanctum')
            ->withHeader('X-Business-Id', $business->uuid)
            ->getJson('/api/customers/'.$customer->id)
            ->assertOk()
            ->assertJsonPath('summary.transaction_count', 1)
            ->json();

        $this->assertGreaterThan(0, (float) $profile['summary']['lifetime_spend']);
        $this->assertSame('SALE-CUSTOMER-001', $profile['recent_sales'][0]['sale_number']);
        $this->assertSame(3, CustomerLoyaltyTransaction::query()->where('customer_id', $customer->id)->where('type', 'sale_earn')->sum('points_delta'));
    }

    public function test_cashier_can_adjust_customer_loyalty_points(): void
    {
        $this->seed(DatabaseSeeder::class);
        [$user, $business] = $this->context();
        $customer = Customer::query()->where('business_id', $business->id)->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->withHeader('X-Business-Id', $business->uuid)
            ->postJson("/api/customers/{$customer->id}/loyalty-transactions", [
                'type' => 'manual_bonus',
                'points_delta' => 40,
                'notes' => 'Opening loyalty bonus',
            ])
            ->assertCreated()
            ->assertJsonPath('loyalty_transaction.type', 'manual_bonus')
            ->assertJsonPath('loyalty_transaction.points_delta', 40);

        $customer->refresh();

        $this->assertSame(40, $customer->loyalty_points);
        $this->assertDatabaseHas('audit_logs', ['action' => 'customer.loyalty_adjusted']);
    }

    public function test_loyalty_adjustment_cannot_make_balance_negative(): void
    {
        $this->seed(DatabaseSeeder::class);
        [$user, $business] = $this->context();
        $customer = Customer::query()->where('business_id', $business->id)->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->withHeader('X-Business-Id', $business->uuid)
            ->postJson("/api/customers/{$customer->id}/loyalty-transactions", [
                'type' => 'redeem',
                'points_delta' => -999,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['points_delta']);
    }

    public function test_customer_endpoints_do_not_leak_other_business_data(): void
    {
        $this->seed(DatabaseSeeder::class);
        [$user, $business] = $this->context();
        $outsideBusiness = Business::query()->create(['uuid' => (string) Str::uuid(), 'name' => 'Outside Business']);
        $outsideCustomer = Customer::query()->create([
            'business_id' => $outsideBusiness->id,
            'name' => 'Outside Customer',
            'phone' => '089999999999',
        ]);

        $this->actingAs($user, 'sanctum')
            ->withHeader('X-Business-Id', $business->uuid)
            ->getJson('/api/customers?search=Outside')
            ->assertOk()
            ->assertJsonPath('customers.data', []);

        $this->actingAs($user, 'sanctum')
            ->withHeader('X-Business-Id', $business->uuid)
            ->getJson('/api/customers/'.$outsideCustomer->id)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['customer_id']);
    }

    public function test_sale_rejects_customer_from_other_business(): void
    {
        $this->seed(DatabaseSeeder::class);
        [$user, $business, $branch] = $this->context();
        $outsideBusiness = Business::query()->create(['uuid' => (string) Str::uuid(), 'name' => 'Outside Business']);
        $outsideCustomer = Customer::query()->create([
            'business_id' => $outsideBusiness->id,
            'name' => 'Wrong Customer',
            'phone' => '087777777777',
        ]);
        $warehouse = Warehouse::query()->where('business_id', $business->id)->where('branch_id', $branch->id)->firstOrFail();
        $product = Product::query()->where('business_id', $business->id)->where('sku', 'KAWI-RICE-001')->firstOrFail();
        $shift = $this->openShift($user, $business, $branch, 'SHIFT-CUSTOMER-INVALID');

        $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Business-Id' => $business->uuid, 'X-Branch-Id' => $branch->uuid])
            ->postJson('/api/sales', [
                'cashier_shift_id' => $shift->id,
                'warehouse_id' => $warehouse->id,
                'customer_id' => $outsideCustomer->id,
                'sale_number' => 'SALE-CUSTOMER-INVALID',
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 35000],
                ],
                'payments' => [
                    ['method' => 'cash', 'amount' => 38850],
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['customer_id']);
    }

    private function context(): array
    {
        $user = User::query()->where('email', 'owner@kawi.test')->firstOrFail();
        $business = Business::query()->where('name', 'KAWI Demo Business')->firstOrFail();
        $branch = Branch::query()->where('business_id', $business->id)->where('code', 'MAIN')->firstOrFail();

        return [$user, $business, $branch];
    }

    private function openShift(User $user, Business $business, Branch $branch, string $number = 'SHIFT-CUSTOMER-001'): CashierShift
    {
        return CashierShift::query()->create([
            'business_id' => $business->id,
            'branch_id' => $branch->id,
            'user_id' => $user->id,
            'uuid' => (string) Str::uuid(),
            'shift_number' => $number,
            'opening_cash' => 100000,
            'expected_cash' => 100000,
            'status' => 'open',
            'opened_at' => now(),
        ]);
    }
}
