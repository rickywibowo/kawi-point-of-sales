<?php

namespace App\Services\Purchasing;

use App\Models\Account;
use App\Models\Business;
use App\Models\GoodsReceipt;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseReturn;
use App\Models\StockBalance;
use App\Models\StockLedger;
use App\Models\Supplier;
use App\Models\SupplierPayable;
use App\Models\SupplierPayment;
use App\Models\Warehouse;
use App\Services\Accounting\AccountingService;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PurchasingService
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly AccountingService $accounting,
    )
    {
    }

    public function createPurchaseOrder(Business $business, ?int $branchId, array $data, Request $request): PurchaseOrder
    {
        $this->assertSupplier($business->id, $data['supplier_id']);
        $this->assertWarehouse($business->id, $data['warehouse_id'] ?? null);

        foreach ($data['items'] as $index => $item) {
            $this->assertProduct($business->id, $item['product_id'], "items.$index.product_id");
        }

        return DB::transaction(function () use ($business, $branchId, $data, $request): PurchaseOrder {
            [$items, $subtotal, $taxTotal] = $this->calculateItems($data['items'], 'quantity_ordered');

            $po = PurchaseOrder::query()->create([
                'business_id' => $business->id,
                'branch_id' => $branchId,
                'supplier_id' => $data['supplier_id'],
                'warehouse_id' => $data['warehouse_id'] ?? null,
                'uuid' => (string) Str::uuid(),
                'po_number' => $data['po_number'],
                'status' => 'draft',
                'order_date' => $data['order_date'] ?? now()->toDateString(),
                'expected_date' => $data['expected_date'] ?? null,
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'grand_total' => $subtotal + $taxTotal,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($items as $item) {
                $po->items()->create($item);
            }

            $po->load(['supplier', 'warehouse', 'items.product']);
            $this->audit->record('purchase_order.created', $po, after: $po->toArray(), request: $request);

            return $po;
        });
    }

    public function approvePurchaseOrder(Business $business, PurchaseOrder $po, Request $request): PurchaseOrder
    {
        abort_unless($po->business_id === $business->id, 403);

        $po->update([
            'status' => 'approved',
            'approved_by' => $request->user()?->id,
            'approved_at' => now(),
        ]);

        $this->audit->record('purchase_order.approved', $po, after: $po->fresh()->toArray(), request: $request);

        return $po->fresh(['supplier', 'warehouse', 'items.product']);
    }

    public function postGoodsReceipt(Business $business, ?int $branchId, array $data, Request $request): GoodsReceipt
    {
        $this->assertSupplier($business->id, $data['supplier_id']);
        $warehouse = $this->assertWarehouse($business->id, $data['warehouse_id']);

        foreach ($data['items'] as $index => $item) {
            $this->assertProduct($business->id, $item['product_id'], "items.$index.product_id");
        }

        return DB::transaction(function () use ($business, $branchId, $warehouse, $data, $request): GoodsReceipt {
            [$items, $subtotal, $taxTotal] = $this->calculateItems($data['items'], 'quantity_received');

            $receipt = GoodsReceipt::query()->create([
                'business_id' => $business->id,
                'branch_id' => $branchId ?? $warehouse->branch_id,
                'purchase_order_id' => $data['purchase_order_id'] ?? null,
                'supplier_id' => $data['supplier_id'],
                'warehouse_id' => $warehouse->id,
                'uuid' => (string) Str::uuid(),
                'receipt_number' => $data['receipt_number'],
                'status' => 'posted',
                'received_date' => $data['received_date'] ?? now()->toDateString(),
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'grand_total' => $subtotal + $taxTotal,
                'received_by' => $request->user()?->id,
                'posted_at' => now(),
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($items as $item) {
                $receiptItem = $receipt->items()->create($item);
                $this->recordPurchaseReceipt($business->id, $receipt->branch_id, $warehouse->id, $receiptItem, $receipt, $request->user()?->id);

                if ($receiptItem->purchase_order_item_id) {
                    PurchaseOrderItem::query()
                        ->whereKey($receiptItem->purchase_order_item_id)
                        ->increment('quantity_received', (float) $receiptItem->quantity_received);
                }
            }

            SupplierPayable::query()->create([
                'business_id' => $business->id,
                'branch_id' => $receipt->branch_id,
                'supplier_id' => $receipt->supplier_id,
                'goods_receipt_id' => $receipt->id,
                'uuid' => (string) Str::uuid(),
                'payable_number' => 'AP-'.$receipt->receipt_number,
                'amount' => $receipt->grand_total,
                'paid_amount' => 0,
                'status' => 'open',
                'due_date' => $data['due_date'] ?? null,
            ]);

            $receipt->load(['supplier', 'warehouse', 'items.product']);
            $this->accounting->postGoodsReceiptJournal($receipt, $request);
            $this->audit->record('goods_receipt.posted', $receipt, after: $receipt->toArray(), request: $request);

            return $receipt;
        });
    }

    public function postPurchaseReturn(Business $business, ?int $branchId, array $data, Request $request): PurchaseReturn
    {
        $this->assertSupplier($business->id, $data['supplier_id']);
        $receipt = GoodsReceipt::query()
            ->where('business_id', $business->id)
            ->where('supplier_id', $data['supplier_id'])
            ->whereKey($data['goods_receipt_id'])
            ->with('items')
            ->first();

        if (! $receipt) {
            throw ValidationException::withMessages(['goods_receipt_id' => ['The selected goods receipt is outside the active business or supplier.']]);
        }

        foreach ($data['items'] as $index => $item) {
            $this->assertProduct($business->id, $item['product_id'], "items.$index.product_id");
            $this->assertReceiptItem($receipt, $item, $index);
        }

        return DB::transaction(function () use ($business, $branchId, $receipt, $data, $request): PurchaseReturn {
            [$items, $subtotal, $taxTotal] = $this->calculateItems($data['items'], 'quantity_returned');

            $return = PurchaseReturn::query()->create([
                'business_id' => $business->id,
                'branch_id' => $branchId ?? $receipt->branch_id,
                'supplier_id' => $data['supplier_id'],
                'goods_receipt_id' => $receipt->id,
                'uuid' => (string) Str::uuid(),
                'return_number' => $data['return_number'],
                'status' => 'posted',
                'return_date' => $data['return_date'] ?? now()->toDateString(),
                'grand_total' => $subtotal + $taxTotal,
                'reason' => $data['reason'] ?? null,
            ]);

            foreach ($items as $index => $item) {
                $item['goods_receipt_item_id'] = $data['items'][$index]['goods_receipt_item_id'] ?? null;
                $item['quantity_returned'] = $data['items'][$index]['quantity_returned'];
                unset($item['quantity_received'], $item['quantity_ordered']);
                $returnItem = $return->items()->create($item + ['reason' => $data['items'][$index]['reason'] ?? null]);
                $this->recordPurchaseReturn($business->id, $return->branch_id, $receipt->warehouse_id, $returnItem, $return, $request->user()?->id);
            }

            $this->applyPayableReturn($business->id, $receipt->id, (float) $return->grand_total);

            $return->load('items.product');
            $this->audit->record('purchase_return.posted', $return, after: $return->toArray(), request: $request);

            return $return;
        });
    }

    public function postSupplierPayment(Business $business, ?int $branchId, SupplierPayable $payable, array $data, Request $request): SupplierPayment
    {
        abort_unless($payable->business_id === $business->id, 403);

        if ($branchId !== null && (int) $payable->branch_id !== $branchId) {
            abort(403);
        }

        $remaining = round((float) $payable->amount - (float) $payable->paid_amount, 2);

        if ($remaining <= 0 || $payable->status === 'closed') {
            throw ValidationException::withMessages(['supplier_payable_id' => ['Supplier payable is already closed.']]);
        }

        $amount = round((float) $data['amount'], 2);

        if ($amount > $remaining) {
            throw ValidationException::withMessages(['amount' => ['Payment amount cannot exceed payable remaining balance.']]);
        }

        $cashAccount = Account::query()
            ->forBusiness($business->id)
            ->whereKey($data['cash_account_id'] ?? null)
            ->where('is_cash', true)
            ->first()
            ?? Account::query()->forBusiness($business->id)->where('code', '1100')->where('is_cash', true)->first();

        if (! $cashAccount) {
            throw ValidationException::withMessages(['cash_account_id' => ['Cash account must belong to the active business.']]);
        }

        return DB::transaction(function () use ($business, $branchId, $payable, $cashAccount, $data, $amount, $request): SupplierPayment {
            $payment = SupplierPayment::query()->create([
                'business_id' => $business->id,
                'branch_id' => $branchId ?? $payable->branch_id,
                'supplier_id' => $payable->supplier_id,
                'supplier_payable_id' => $payable->id,
                'cash_account_id' => $cashAccount->id,
                'uuid' => (string) Str::uuid(),
                'payment_number' => $data['payment_number'],
                'payment_date' => $data['payment_date'] ?? now()->toDateString(),
                'amount' => $amount,
                'payment_method' => $data['payment_method'] ?? 'cash',
                'reference_number' => $data['reference_number'] ?? null,
                'notes' => $data['notes'] ?? null,
                'posted_at' => now(),
                'posted_by' => $request->user()?->id,
            ]);

            $newPaidAmount = round((float) $payable->paid_amount + $amount, 2);
            $payable->update([
                'paid_amount' => $newPaidAmount,
                'status' => $newPaidAmount >= (float) $payable->amount ? 'closed' : 'partial',
            ]);

            $accounts = Account::query()->forBusiness($business->id)->get()->keyBy('code');
            $accountsPayable = $accounts['2100'] ?? null;

            if (! $accountsPayable) {
                throw ValidationException::withMessages(['supplier_payable_id' => ['Accounts payable account is not configured.']]);
            }

            $this->accounting->postJournal($business, $payment->branch_id, [
                'journal_number' => 'JE-AP-PAY-'.$payment->payment_number,
                'journal_date' => $payment->payment_date->toDateString(),
                'source_type' => SupplierPayment::class,
                'source_id' => $payment->id,
                'description' => 'Supplier payment '.$payment->payment_number,
                'lines' => [
                    ['account_id' => $accountsPayable->id, 'debit' => $amount, 'credit' => 0, 'description' => 'Pembayaran utang '.$payable->payable_number],
                    ['account_id' => $cashAccount->id, 'debit' => 0, 'credit' => $amount, 'description' => 'Kas keluar '.$payment->payment_number],
                ],
            ], $request);

            $payment->load(['supplier', 'payable', 'cashAccount']);
            $this->audit->record('supplier_payment.posted', $payment, after: $payment->toArray(), request: $request);

            return $payment;
        });
    }

    private function calculateItems(array $inputItems, string $quantityField): array
    {
        $items = [];
        $subtotal = 0;
        $taxTotal = 0;

        foreach ($inputItems as $item) {
            $quantity = (float) $item[$quantityField];
            $unitCost = (float) $item['unit_cost'];
            $taxRate = (float) ($item['tax_rate'] ?? 0);
            $lineSubtotal = round($quantity * $unitCost, 2);
            $lineTax = round($lineSubtotal * ($taxRate / 100), 2);
            $subtotal += $lineSubtotal;
            $taxTotal += $lineTax;

            $items[] = [
                'purchase_order_item_id' => $item['purchase_order_item_id'] ?? null,
                'product_id' => $item['product_id'],
                'unit_of_measure_id' => $item['unit_of_measure_id'] ?? null,
                $quantityField => $quantity,
                'unit_cost' => $unitCost,
                'tax_rate' => $taxRate,
                'tax_total' => $lineTax,
                'line_total' => $lineSubtotal + $lineTax,
            ];
        }

        return [$items, round($subtotal, 2), round($taxTotal, 2)];
    }

    private function recordPurchaseReceipt(int $businessId, ?int $branchId, int $warehouseId, $receiptItem, GoodsReceipt $receipt, ?int $userId): void
    {
        StockLedger::query()->create([
            'business_id' => $businessId,
            'branch_id' => $branchId,
            'warehouse_id' => $warehouseId,
            'product_id' => $receiptItem->product_id,
            'unit_of_measure_id' => $receiptItem->unit_of_measure_id,
            'uuid' => (string) Str::uuid(),
            'movement_type' => 'purchase_receipt',
            'quantity_in' => $receiptItem->quantity_received,
            'quantity_out' => 0,
            'unit_cost' => $receiptItem->unit_cost,
            'total_cost' => (float) $receiptItem->quantity_received * (float) $receiptItem->unit_cost,
            'source_type' => GoodsReceipt::class,
            'source_id' => $receipt->id,
            'reference_number' => $receipt->receipt_number,
            'occurred_at' => now(),
            'created_by' => $userId,
        ]);

        $balance = StockBalance::query()->firstOrCreate(
            ['warehouse_id' => $warehouseId, 'product_id' => $receiptItem->product_id],
            ['business_id' => $businessId, 'branch_id' => $branchId, 'quantity_on_hand' => 0, 'average_cost' => 0, 'stock_value' => 0],
        );

        $newQuantity = (float) $balance->quantity_on_hand + (float) $receiptItem->quantity_received;
        $newValue = (float) $balance->stock_value + ((float) $receiptItem->quantity_received * (float) $receiptItem->unit_cost);

        $balance->update([
            'quantity_on_hand' => $newQuantity,
            'average_cost' => $newQuantity > 0 ? round($newValue / $newQuantity, 2) : 0,
            'stock_value' => round($newValue, 2),
        ]);
    }

    private function recordPurchaseReturn(int $businessId, ?int $branchId, int $warehouseId, $returnItem, PurchaseReturn $return, ?int $userId): void
    {
        StockLedger::query()->create([
            'business_id' => $businessId,
            'branch_id' => $branchId,
            'warehouse_id' => $warehouseId,
            'product_id' => $returnItem->product_id,
            'unit_of_measure_id' => $returnItem->unit_of_measure_id,
            'uuid' => (string) Str::uuid(),
            'movement_type' => 'purchase_return',
            'quantity_in' => 0,
            'quantity_out' => $returnItem->quantity_returned,
            'unit_cost' => $returnItem->unit_cost,
            'total_cost' => (float) $returnItem->quantity_returned * (float) $returnItem->unit_cost,
            'source_type' => PurchaseReturn::class,
            'source_id' => $return->id,
            'reference_number' => $return->return_number,
            'notes' => $returnItem->reason ?? $return->reason,
            'occurred_at' => now(),
            'created_by' => $userId,
        ]);

        $balance = StockBalance::query()->firstOrCreate(
            ['warehouse_id' => $warehouseId, 'product_id' => $returnItem->product_id],
            ['business_id' => $businessId, 'branch_id' => $branchId, 'quantity_on_hand' => 0, 'average_cost' => 0, 'stock_value' => 0],
        );

        $newQuantity = max((float) $balance->quantity_on_hand - (float) $returnItem->quantity_returned, 0);
        $newValue = max((float) $balance->stock_value - ((float) $returnItem->quantity_returned * (float) $returnItem->unit_cost), 0);

        $balance->update([
            'quantity_on_hand' => $newQuantity,
            'average_cost' => $newQuantity > 0 ? round($newValue / $newQuantity, 2) : 0,
            'stock_value' => round($newValue, 2),
        ]);
    }

    private function applyPayableReturn(int $businessId, int $receiptId, float $returnTotal): void
    {
        $payable = SupplierPayable::query()
            ->where('business_id', $businessId)
            ->where('goods_receipt_id', $receiptId)
            ->first();

        if (! $payable) {
            return;
        }

        $newAmount = max((float) $payable->amount - $returnTotal, 0);

        $payable->update([
            'amount' => round($newAmount, 2),
            'status' => $newAmount <= (float) $payable->paid_amount ? 'closed' : $payable->status,
        ]);
    }

    private function assertSupplier(int $businessId, int $supplierId): void
    {
        if (! Supplier::query()->forBusiness($businessId)->whereKey($supplierId)->exists()) {
            throw ValidationException::withMessages(['supplier_id' => ['The selected supplier is outside the active business.']]);
        }
    }

    private function assertWarehouse(int $businessId, ?int $warehouseId): ?Warehouse
    {
        if (! $warehouseId) {
            return null;
        }

        $warehouse = Warehouse::query()->where('business_id', $businessId)->whereKey($warehouseId)->first();

        if (! $warehouse) {
            throw ValidationException::withMessages(['warehouse_id' => ['The selected warehouse is outside the active business.']]);
        }

        return $warehouse;
    }

    private function assertProduct(int $businessId, int $productId, string $field): void
    {
        if (! Product::query()->forBusiness($businessId)->whereKey($productId)->exists()) {
            throw ValidationException::withMessages([$field => ['The selected product is outside the active business.']]);
        }
    }

    private function assertReceiptItem(GoodsReceipt $receipt, array $item, int $index): void
    {
        if (empty($item['goods_receipt_item_id'])) {
            return;
        }

        $receiptItem = $receipt->items->firstWhere('id', $item['goods_receipt_item_id']);

        if (! $receiptItem || (int) $receiptItem->product_id !== (int) $item['product_id']) {
            throw ValidationException::withMessages(["items.$index.goods_receipt_item_id" => ['The selected receipt item does not match this goods receipt.']]);
        }

        if ((float) $item['quantity_returned'] > (float) $receiptItem->quantity_received) {
            throw ValidationException::withMessages(["items.$index.quantity_returned" => ['Returned quantity cannot exceed received quantity.']]);
        }
    }
}
