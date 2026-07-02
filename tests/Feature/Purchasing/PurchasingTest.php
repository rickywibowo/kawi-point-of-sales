<?php

namespace Tests\Feature\Purchasing;

use App\Models\Branch;
use App\Models\Business;
use App\Models\GoodsReceipt;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\StockBalance;
use App\Models\SupplierPayable;
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

    public function test_purchase_return_posts_stock_out_and_reduces_supplier_payable(): void
    {
        $this->seed(DatabaseSeeder::class);
        [$user, $business, $branch, $supplier, $warehouse, $product] = $this->context();

        $receiptId = $this->postGoodsReceipt($user, $business, $branch, $supplier, $warehouse, $product, 'GR-RETURN-001', 5);
        $receipt = GoodsReceipt::query()->with('items')->findOrFail($receiptId);
        $before = (float) StockBalance::query()->where('warehouse_id', $warehouse->id)->where('product_id', $product->id)->firstOrFail()->quantity_on_hand;

        $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Business-Id' => $business->uuid, 'X-Branch-Id' => $branch->uuid])
            ->postJson('/api/purchase-returns', [
                'supplier_id' => $supplier->id,
                'goods_receipt_id' => $receipt->id,
                'return_number' => 'PR-TEST-001',
                'reason' => 'Barang rusak',
                'items' => [
                    [
                        'goods_receipt_item_id' => $receipt->items->first()->id,
                        'product_id' => $product->id,
                        'quantity_returned' => 2,
                        'unit_cost' => 9000,
                        'tax_rate' => 11,
                    ],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('purchase_return.status', 'posted')
            ->assertJsonPath('purchase_return.grand_total', '19980.00');

        $after = (float) StockBalance::query()->where('warehouse_id', $warehouse->id)->where('product_id', $product->id)->firstOrFail()->quantity_on_hand;
        $payable = SupplierPayable::query()->where('goods_receipt_id', $receipt->id)->firstOrFail();

        $this->assertSame($before - 2, $after);
        $this->assertSame('29970.00', (string) $payable->amount);
        $this->assertDatabaseHas('stock_ledgers', ['reference_number' => 'PR-TEST-001', 'movement_type' => 'purchase_return']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'purchase_return.posted']);
    }

    public function test_purchase_return_rejects_quantity_above_received_quantity(): void
    {
        $this->seed(DatabaseSeeder::class);
        [$user, $business, $branch, $supplier, $warehouse, $product] = $this->context();

        $receiptId = $this->postGoodsReceipt($user, $business, $branch, $supplier, $warehouse, $product, 'GR-RETURN-INVALID', 1);
        $receipt = GoodsReceipt::query()->with('items')->findOrFail($receiptId);

        $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Business-Id' => $business->uuid, 'X-Branch-Id' => $branch->uuid])
            ->postJson('/api/purchase-returns', [
                'supplier_id' => $supplier->id,
                'goods_receipt_id' => $receipt->id,
                'return_number' => 'PR-INVALID-001',
                'items' => [
                    [
                        'goods_receipt_item_id' => $receipt->items->first()->id,
                        'product_id' => $product->id,
                        'quantity_returned' => 2,
                        'unit_cost' => 9000,
                    ],
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['items.0.quantity_returned']);
    }

    public function test_user_can_pay_supplier_payable_and_close_balance(): void
    {
        $this->seed(DatabaseSeeder::class);
        [$user, $business, $branch, $supplier, $warehouse, $product] = $this->context();
        $receiptId = $this->postGoodsReceipt($user, $business, $branch, $supplier, $warehouse, $product, 'GR-PAY-001', 3);
        $payable = SupplierPayable::query()->where('goods_receipt_id', $receiptId)->firstOrFail();
        $cashAccount = Account::query()->where('business_id', $business->id)->where('code', '1100')->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Business-Id' => $business->uuid, 'X-Branch-Id' => $branch->uuid])
            ->postJson("/api/supplier-payables/{$payable->id}/payments", [
                'payment_number' => 'PAY-TEST-001',
                'payment_date' => '2026-07-03',
                'cash_account_id' => $cashAccount->id,
                'amount' => 29970,
                'payment_method' => 'cash',
            ])
            ->assertCreated()
            ->assertJsonPath('supplier_payment.payment_number', 'PAY-TEST-001')
            ->assertJsonPath('supplier_payment.amount', '29970.00');

        $payable->refresh();
        $journal = JournalEntry::query()->where('journal_number', 'JE-AP-PAY-PAY-TEST-001')->with('lines.account')->firstOrFail();

        $this->assertSame('closed', $payable->status);
        $this->assertSame('29970.00', (string) $payable->paid_amount);
        $this->assertSame('posted', $journal->status);
        $this->assertTrue($journal->lines->contains(fn ($line) => $line->account->code === '2100' && (string) $line->debit === '29970.00'));
        $this->assertTrue($journal->lines->contains(fn ($line) => $line->account->code === '1100' && (string) $line->credit === '29970.00'));
        $this->assertDatabaseHas('audit_logs', ['action' => 'supplier_payment.posted']);
    }

    public function test_supplier_payment_rejects_amount_above_remaining_balance(): void
    {
        $this->seed(DatabaseSeeder::class);
        [$user, $business, $branch, $supplier, $warehouse, $product] = $this->context();
        $receiptId = $this->postGoodsReceipt($user, $business, $branch, $supplier, $warehouse, $product, 'GR-PAY-BAD', 1);
        $payable = SupplierPayable::query()->where('goods_receipt_id', $receiptId)->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Business-Id' => $business->uuid, 'X-Branch-Id' => $branch->uuid])
            ->postJson("/api/supplier-payables/{$payable->id}/payments", [
                'payment_number' => 'PAY-INVALID-001',
                'amount' => 999999,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['amount']);
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

    private function postGoodsReceipt(User $user, Business $business, Branch $branch, Supplier $supplier, Warehouse $warehouse, Product $product, string $number, int $quantity): int
    {
        return $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Business-Id' => $business->uuid, 'X-Branch-Id' => $branch->uuid])
            ->postJson('/api/goods-receipts', [
                'supplier_id' => $supplier->id,
                'warehouse_id' => $warehouse->id,
                'receipt_number' => $number,
                'items' => [
                    ['product_id' => $product->id, 'quantity_received' => $quantity, 'unit_cost' => 9000, 'tax_rate' => 11],
                ],
            ])
            ->assertCreated()
            ->json('goods_receipt.id');
    }
}
