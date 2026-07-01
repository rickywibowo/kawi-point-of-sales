<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\Branch;
use App\Models\Business;
use App\Models\CashierShift;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AccountingTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_post_balanced_manual_journal(): void
    {
        $this->seed(DatabaseSeeder::class);
        [$user, $business, $branch] = $this->context();
        $cash = Account::query()->where('business_id', $business->id)->where('code', '1100')->firstOrFail();
        $capital = Account::query()->where('business_id', $business->id)->where('code', '3100')->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Business-Id' => $business->uuid, 'X-Branch-Id' => $branch->uuid])
            ->postJson('/api/journal-entries', [
                'journal_number' => 'JE-TEST-001',
                'description' => 'Setoran modal',
                'lines' => [
                    ['account_id' => $cash->id, 'debit' => 100000, 'credit' => 0],
                    ['account_id' => $capital->id, 'debit' => 0, 'credit' => 100000],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('journal_entry.total_debit', '100000.00');

        $this->assertDatabaseHas('audit_logs', ['action' => 'journal.posted']);
    }

    public function test_unbalanced_manual_journal_is_rejected(): void
    {
        $this->seed(DatabaseSeeder::class);
        [$user, $business, $branch] = $this->context();
        $cash = Account::query()->where('business_id', $business->id)->where('code', '1100')->firstOrFail();
        $capital = Account::query()->where('business_id', $business->id)->where('code', '3100')->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Business-Id' => $business->uuid, 'X-Branch-Id' => $branch->uuid])
            ->postJson('/api/journal-entries', [
                'journal_number' => 'JE-TEST-BAD',
                'lines' => [
                    ['account_id' => $cash->id, 'debit' => 100000, 'credit' => 0],
                    ['account_id' => $capital->id, 'debit' => 0, 'credit' => 90000],
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['lines']);
    }

    public function test_completed_sale_creates_auto_journal(): void
    {
        $this->seed(DatabaseSeeder::class);
        [$user, $business, $branch] = $this->context();
        $shift = $this->openShift($user, $business, $branch);
        $warehouse = Warehouse::query()->where('business_id', $business->id)->where('branch_id', $branch->id)->firstOrFail();
        $product = Product::query()->where('business_id', $business->id)->where('sku', 'KAWI-RICE-001')->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Business-Id' => $business->uuid, 'X-Branch-Id' => $branch->uuid])
            ->postJson('/api/sales', [
                'cashier_shift_id' => $shift->id,
                'warehouse_id' => $warehouse->id,
                'sale_number' => 'SALE-ACC-001',
                'idempotency_key' => 'acc-sale-001',
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 35000],
                ],
                'payments' => [
                    ['method' => 'cash', 'amount' => 38850],
                ],
            ])
            ->assertCreated();

        $journal = JournalEntry::query()->where('journal_number', 'JE-SALE-SALE-ACC-001')->with('lines')->firstOrFail();
        $this->assertSame('posted', $journal->status);
        $this->assertSame((string) $journal->total_debit, (string) $journal->total_credit);
    }

    public function test_goods_receipt_creates_auto_journal(): void
    {
        $this->seed(DatabaseSeeder::class);
        [$user, $business, $branch] = $this->context();
        $supplier = Supplier::query()->where('business_id', $business->id)->firstOrFail();
        $warehouse = Warehouse::query()->where('business_id', $business->id)->where('branch_id', $branch->id)->firstOrFail();
        $product = Product::query()->where('business_id', $business->id)->where('sku', 'KAWI-COFFEE-001')->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Business-Id' => $business->uuid, 'X-Branch-Id' => $branch->uuid])
            ->postJson('/api/goods-receipts', [
                'supplier_id' => $supplier->id,
                'warehouse_id' => $warehouse->id,
                'receipt_number' => 'GR-ACC-001',
                'items' => [
                    ['product_id' => $product->id, 'quantity_received' => 3, 'unit_cost' => 9000, 'tax_rate' => 11],
                ],
            ])
            ->assertCreated();

        $journal = JournalEntry::query()->where('journal_number', 'JE-GR-GR-ACC-001')->firstOrFail();
        $this->assertSame('posted', $journal->status);
        $this->assertSame('29970.00', (string) $journal->total_debit);
    }

    public function test_accounting_index_returns_trial_balance_and_profit_loss(): void
    {
        $this->seed(DatabaseSeeder::class);
        [$user, $business, $branch] = $this->context();

        $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Business-Id' => $business->uuid, 'X-Branch-Id' => $branch->uuid])
            ->getJson('/api/accounting')
            ->assertOk()
            ->assertJsonStructure(['accounts', 'journal_entries', 'trial_balance', 'profit_and_loss']);
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
            'shift_number' => 'SHIFT-ACC-001',
            'opening_cash' => 100000,
            'expected_cash' => 100000,
            'status' => 'open',
            'opened_at' => now(),
        ]);
    }
}
