<?php

namespace App\Services\Pos;

use App\Models\Branch;
use App\Models\Business;
use App\Models\CashMovement;
use App\Models\CashDrawerAudit;
use App\Models\CashierShift;
use App\Models\Customer;
use App\Models\DiningTable;
use App\Models\HeldTransaction;
use App\Models\Modifier;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockBalance;
use App\Models\StockLedger;
use App\Models\Warehouse;
use App\Services\Accounting\AccountingService;
use App\Services\Audit\AuditLogger;
use App\Services\Customers\CustomerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PosService
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly AccountingService $accounting,
        private readonly CustomerService $customers,
        private readonly PromotionService $promotions,
        private readonly KitchenService $kitchen,
        private readonly DeliveryService $deliveries,
    )
    {
    }

    public function openShift(Business $business, Branch $branch, array $data, Request $request): CashierShift
    {
        $openShiftExists = CashierShift::query()
            ->where('business_id', $business->id)
            ->where('branch_id', $branch->id)
            ->where('user_id', $request->user()->id)
            ->where('status', 'open')
            ->exists();

        if ($openShiftExists) {
            throw ValidationException::withMessages(['shift' => ['Cashier already has an open shift for this branch.']]);
        }

        return DB::transaction(function () use ($business, $branch, $data, $request): CashierShift {
            $shift = CashierShift::query()->create([
                'business_id' => $business->id,
                'branch_id' => $branch->id,
                'user_id' => $request->user()->id,
                'uuid' => (string) Str::uuid(),
                'shift_number' => $data['shift_number'],
                'opening_cash' => $data['opening_cash'] ?? 0,
                'expected_cash' => $data['opening_cash'] ?? 0,
                'status' => 'open',
                'opened_at' => now(),
                'notes' => $data['notes'] ?? null,
            ]);

            $this->audit->record('cashier_shift.opened', $shift, after: $shift->toArray(), request: $request);

            return $shift;
        });
    }

    public function closeShift(Business $business, Branch $branch, CashierShift $shift, array $data, Request $request): CashierShift
    {
        abort_unless($shift->business_id === $business->id && $shift->branch_id === $branch->id, 403);

        if ($shift->status !== 'open') {
            throw ValidationException::withMessages(['shift' => ['Only open shifts can be closed.']]);
        }

        $cashSales = Sale::query()
            ->where('cashier_shift_id', $shift->id)
            ->where('status', 'completed')
            ->whereHas('payments', fn ($query) => $query->where('method', 'cash'))
            ->with('payments')
            ->get()
            ->flatMap->payments
            ->where('method', 'cash')
            ->sum(fn ($payment) => (float) $payment->amount);

        $cashIn = CashMovement::query()
            ->where('cashier_shift_id', $shift->id)
            ->where('type', 'cash_in')
            ->sum('amount');
        $cashOut = CashMovement::query()
            ->where('cashier_shift_id', $shift->id)
            ->where('type', 'cash_out')
            ->sum('amount');
        $expectedCash = (float) $shift->opening_cash + $cashSales + (float) $cashIn - (float) $cashOut;
        $actualCash = (float) $data['actual_cash'];
        $drawerCounts = $data['drawer_counts'] ?? null;
        $countedCash = $drawerCounts ? $this->countDrawerCash($drawerCounts) : $actualCash;
        $variance = round($actualCash - $expectedCash, 2);

        if (round($countedCash, 2) !== round($actualCash, 2)) {
            throw ValidationException::withMessages(['drawer_counts' => ['Drawer count total must match actual cash.']]);
        }

        if ($variance !== 0.0 && empty($data['variance_reason'])) {
            throw ValidationException::withMessages(['variance_reason' => ['Variance reason is required when cash difference is not zero.']]);
        }

        return DB::transaction(function () use ($business, $branch, $shift, $data, $request, $expectedCash, $actualCash, $countedCash, $variance, $drawerCounts): CashierShift {
            $shift->update([
                'expected_cash' => $expectedCash,
                'actual_cash' => $actualCash,
                'cash_difference' => $variance,
                'status' => 'closed',
                'closed_at' => now(),
                'notes' => $data['notes'] ?? $shift->notes,
            ]);

            if ($drawerCounts) {
                $drawerAudit = CashDrawerAudit::query()->create([
                    'business_id' => $business->id,
                    'branch_id' => $branch->id,
                    'cashier_shift_id' => $shift->id,
                    'user_id' => $request->user()->id,
                    'denomination_breakdown' => $this->normalizeDrawerCounts($drawerCounts),
                    'expected_cash' => $expectedCash,
                    'counted_cash' => $countedCash,
                    'variance_amount' => $variance,
                    'status' => $this->drawerAuditStatus($variance, (bool) ($data['variance_approved'] ?? false)),
                    'variance_reason' => $data['variance_reason'] ?? null,
                    'approved_by' => $variance !== 0.0 && (bool) ($data['variance_approved'] ?? false) ? $request->user()->id : null,
                    'audited_at' => now(),
                ]);

                $this->audit->record('cash_drawer.audit_created', $drawerAudit, after: $drawerAudit->toArray(), request: $request);
            }

            $closed = $shift->fresh()->load('drawerAudit');
            $this->audit->record('cashier_shift.closed', $closed, after: $closed->toArray(), request: $request);

            return $closed;
        });
    }

    public function recordCashMovement(Business $business, Branch $branch, CashierShift $shift, array $data, Request $request): CashMovement
    {
        abort_unless($shift->business_id === $business->id && $shift->branch_id === $branch->id, 403);

        if ($shift->status !== 'open') {
            throw ValidationException::withMessages(['cashier_shift_id' => ['Cash movement requires an open shift.']]);
        }

        return DB::transaction(function () use ($business, $branch, $shift, $data, $request): CashMovement {
            $movement = CashMovement::query()->create([
                'business_id' => $business->id,
                'branch_id' => $branch->id,
                'cashier_shift_id' => $shift->id,
                'user_id' => $request->user()->id,
                'type' => $data['type'],
                'amount' => $data['amount'],
                'reason' => $data['reason'] ?? null,
            ]);

            $delta = $data['type'] === 'cash_in' ? (float) $data['amount'] : -1 * (float) $data['amount'];
            $shift->update(['expected_cash' => (float) $shift->expected_cash + $delta]);

            $this->audit->record('cash_movement.created', $movement, after: $movement->toArray(), request: $request);

            return $movement->load('cashierShift');
        });
    }

    public function holdTransaction(Business $business, Branch $branch, array $data, Request $request): HeldTransaction
    {
        $this->assertCustomerInBusiness($business->id, $data['customer_id'] ?? null);

        return DB::transaction(function () use ($business, $branch, $data, $request): HeldTransaction {
            $held = HeldTransaction::query()->create([
                'business_id' => $business->id,
                'branch_id' => $branch->id,
                'cashier_shift_id' => $data['cashier_shift_id'] ?? null,
                'customer_id' => $data['customer_id'] ?? null,
                'cashier_id' => $request->user()->id,
                'uuid' => (string) Str::uuid(),
                'hold_number' => $data['hold_number'],
                'payload' => $data['payload'],
                'held_at' => now(),
            ]);

            $this->audit->record('sale.held', $held, after: $held->toArray(), request: $request);

            return $held;
        });
    }

    public function completeSale(Business $business, Branch $branch, array $data, Request $request): Sale
    {
        if (! empty($data['idempotency_key'])) {
            $existing = Sale::query()
                ->where('business_id', $business->id)
                ->where('idempotency_key', $data['idempotency_key'])
                ->first();

            if ($existing) {
                return $existing->load(['items.modifiers', 'payments']);
            }
        }

        $shift = CashierShift::query()
            ->where('business_id', $business->id)
            ->where('branch_id', $branch->id)
            ->where('status', 'open')
            ->whereKey($data['cashier_shift_id'])
            ->first();

        if (! $shift) {
            throw ValidationException::withMessages(['cashier_shift_id' => ['An open shift is required.']]);
        }

        $warehouse = Warehouse::query()
            ->where('business_id', $business->id)
            ->where('branch_id', $branch->id)
            ->whereKey($data['warehouse_id'])
            ->first();

        if (! $warehouse) {
            throw ValidationException::withMessages(['warehouse_id' => ['The selected warehouse is outside the active branch.']]);
        }

        $this->assertCustomerInBusiness($business->id, $data['customer_id'] ?? null);
        $diningTable = $this->assertDiningTable($business->id, $branch->id, $data);

        return DB::transaction(function () use ($business, $branch, $warehouse, $shift, $diningTable, $data, $request): Sale {
            [$items, $subtotal, $discountTotal, $taxTotal] = $this->prepareItems($business->id, $branch->id, $data['items']);
            [$promotion, $promotionDiscount] = $this->promotions->apply($business, $data['promotion_code'] ?? null, max($subtotal - $discountTotal, 0));
            $discountTotal = round($discountTotal + $promotionDiscount, 2);
            $serviceChargeTotal = (float) ($data['service_charge_total'] ?? 0);
            $deliveryFeeTotal = ($data['type'] ?? 'takeaway') === 'delivery' ? round((float) ($data['delivery']['fee'] ?? 0), 2) : 0.0;
            $grandTotal = round($subtotal - $discountTotal + $taxTotal + $serviceChargeTotal + $deliveryFeeTotal, 2);
            $paidTotal = collect($data['payments'])->sum(fn ($payment) => (float) $payment['amount']);

            if ($paidTotal < $grandTotal) {
                throw ValidationException::withMessages(['payments' => ['Paid total must cover grand total.']]);
            }

            $sale = Sale::query()->create([
                'business_id' => $business->id,
                'branch_id' => $branch->id,
                'cashier_shift_id' => $shift->id,
                'customer_id' => $data['customer_id'] ?? null,
                'dining_table_id' => $diningTable?->id,
                'promotion_id' => $promotion?->id,
                'promotion_code' => $promotion?->code,
                'promotion_discount_total' => $promotionDiscount,
                'cashier_id' => $request->user()->id,
                'uuid' => (string) Str::uuid(),
                'sale_number' => $data['sale_number'],
                'idempotency_key' => $data['idempotency_key'] ?? null,
                'type' => $data['type'] ?? 'takeaway',
                'status' => 'completed',
                'subtotal' => $subtotal,
                'discount_total' => $discountTotal,
                'tax_total' => $taxTotal,
                'service_charge_total' => $serviceChargeTotal,
                'delivery_fee_total' => $deliveryFeeTotal,
                'grand_total' => $grandTotal,
                'paid_total' => $paidTotal,
                'change_total' => max($paidTotal - $grandTotal, 0),
                'notes' => $data['notes'] ?? null,
                'sold_at' => now(),
            ]);

            foreach ($items as $item) {
                $saleItem = $sale->items()->create($item['attributes']);

                foreach ($item['modifiers'] as $modifier) {
                    $saleItem->modifiers()->create($modifier);
                }

                $this->recordSalesConsumption($business->id, $branch->id, $warehouse->id, $item['product'], (float) $item['attributes']['quantity'], $sale);
            }

            foreach ($data['payments'] as $payment) {
                $sale->payments()->create([
                    'business_id' => $business->id,
                    'branch_id' => $branch->id,
                    'uuid' => (string) Str::uuid(),
                    'method' => $payment['method'],
                    'amount' => $payment['amount'],
                    'reference' => $payment['reference'] ?? null,
                    'metadata' => $payment['metadata'] ?? null,
                ]);
            }

            if ($diningTable) {
                $diningTable->update(['status' => 'cleaning']);
            }

            if ($promotion) {
                $this->promotions->markUsed($promotion);
            }

            $sale->load(['items.modifiers', 'payments']);

            if ($sale->type === 'delivery') {
                $this->deliveries->createForSale($sale, $data, $request);
            }

            $this->kitchen->createTicketForSale($sale, $request);
            $this->accounting->postSaleJournal($sale, $request);

            if ($sale->customer_id) {
                $customer = Customer::query()->where('business_id', $business->id)->whereKey($sale->customer_id)->first();

                if ($customer) {
                    $this->customers->earnFromSale($customer, $sale, $request);
                }
            }

            $this->audit->record('sale.completed', $sale, after: $sale->toArray(), request: $request);

            return $sale;
        });
    }

    public function voidSale(Business $business, Branch $branch, Sale $sale, array $data, Request $request): Sale
    {
        abort_unless($sale->business_id === $business->id && $sale->branch_id === $branch->id, 403);

        if ($sale->status !== 'completed') {
            throw ValidationException::withMessages(['sale' => ['Only completed sales can be voided.']]);
        }

        return DB::transaction(function () use ($sale, $data, $request): Sale {
            $before = $sale->toArray();
            $this->reverseSalesConsumption($sale, 'sales_void', $request->user()->id);

            $sale->update([
                'status' => 'voided',
                'voided_at' => now(),
                'voided_by' => $request->user()->id,
                'notes' => $this->appendStatusReason($sale->notes, 'Void', $data['reason'] ?? null),
            ]);

            $sale->refresh()->load(['items.modifiers', 'payments']);
            $this->audit->record('sale.voided', $sale, before: $before, after: $sale->toArray(), request: $request);

            return $sale;
        });
    }

    public function refundSale(Business $business, Branch $branch, Sale $sale, array $data, Request $request): Sale
    {
        abort_unless($sale->business_id === $business->id && $sale->branch_id === $branch->id, 403);

        if ($sale->status !== 'completed') {
            throw ValidationException::withMessages(['sale' => ['Only completed sales can be refunded.']]);
        }

        return DB::transaction(function () use ($sale, $data, $request): Sale {
            $before = $sale->toArray();
            $this->reverseSalesConsumption($sale, 'sales_refund', $request->user()->id);

            $sale->update([
                'status' => 'refunded',
                'refunded_at' => now(),
                'refunded_by' => $request->user()->id,
                'notes' => $this->appendStatusReason($sale->notes, 'Refund', $data['reason'] ?? null),
            ]);

            $sale->refresh()->load(['items.modifiers', 'payments']);
            $this->audit->record('sale.refunded', $sale, before: $before, after: $sale->toArray(), request: $request);

            return $sale;
        });
    }

    private function prepareItems(int $businessId, int $branchId, array $inputItems): array
    {
        $items = [];
        $subtotal = 0;
        $discountTotal = 0;
        $taxTotal = 0;

        foreach ($inputItems as $index => $input) {
            $product = Product::query()
                ->forBusiness($businessId)
                ->with('tax')
                ->whereKey($input['product_id'])
                ->first();

            if (! $product) {
                throw ValidationException::withMessages(["items.$index.product_id" => ['The selected product is outside the active business.']]);
            }

            $quantity = (float) $input['quantity'];
            $unitPrice = (float) ($input['unit_price'] ?? $product->branchPrices()->where('branch_id', $branchId)->value('price') ?? $product->base_price);
            $modifierTotal = 0;
            $modifiers = [];

            foreach ($input['modifiers'] ?? [] as $modifierInput) {
                $modifier = Modifier::query()->forBusiness($businessId)->whereKey($modifierInput['modifier_id'])->first();

                if (! $modifier) {
                    continue;
                }

                $modifierTotal += (float) $modifier->price_delta;
                $modifiers[] = [
                    'modifier_id' => $modifier->id,
                    'modifier_name' => $modifier->name,
                    'price_delta' => $modifier->price_delta,
                ];
            }

            $grossLine = ($unitPrice + $modifierTotal) * $quantity;
            $lineDiscount = (float) ($input['discount_total'] ?? 0);
            $taxRate = $product->tax ? (float) $product->tax->rate : 0;
            $lineTax = round(max($grossLine - $lineDiscount, 0) * ($taxRate / 100), 2);
            $lineTotal = round($grossLine - $lineDiscount + $lineTax, 2);

            $subtotal += $grossLine;
            $discountTotal += $lineDiscount;
            $taxTotal += $lineTax;

            $items[] = [
                'product' => $product,
                'attributes' => [
                    'business_id' => $businessId,
                    'branch_id' => $branchId,
                    'product_id' => $product->id,
                    'product_variant_id' => $input['product_variant_id'] ?? null,
                    'product_name' => $product->name,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'discount_total' => $lineDiscount,
                    'tax_total' => $lineTax,
                    'line_total' => $lineTotal,
                    'notes' => $input['notes'] ?? null,
                ],
                'modifiers' => $modifiers,
            ];
        }

        return [$items, round($subtotal, 2), round($discountTotal, 2), round($taxTotal, 2)];
    }

    private function assertCustomerInBusiness(int $businessId, ?int $customerId): void
    {
        if ($customerId === null) {
            return;
        }

        $exists = Customer::query()
            ->forBusiness($businessId)
            ->whereKey($customerId)
            ->exists();

        if (! $exists) {
            throw ValidationException::withMessages(['customer_id' => ['The selected customer is outside the active business.']]);
        }
    }

    private function assertDiningTable(int $businessId, int $branchId, array $data): ?DiningTable
    {
        if (($data['type'] ?? 'takeaway') !== 'dine_in') {
            return null;
        }

        if (empty($data['dining_table_id'])) {
            throw ValidationException::withMessages(['dining_table_id' => ['Dine-in sales require a dining table.']]);
        }

        $table = DiningTable::query()
            ->where('business_id', $businessId)
            ->where('branch_id', $branchId)
            ->whereKey($data['dining_table_id'])
            ->first();

        if (! $table) {
            throw ValidationException::withMessages(['dining_table_id' => ['The selected dining table is outside the active branch.']]);
        }

        if (! in_array($table->status, ['available', 'reserved'], true)) {
            throw ValidationException::withMessages(['dining_table_id' => ['The selected dining table is not available.']]);
        }

        return $table;
    }

    private function reverseSalesConsumption(Sale $sale, string $movementType, ?int $userId): void
    {
        $sale->loadMissing('items.product');

        foreach ($sale->items as $item) {
            $sourceLedger = StockLedger::query()
                ->where('source_type', Sale::class)
                ->where('source_id', $sale->id)
                ->where('product_id', $item->product_id)
                ->where('movement_type', 'sales_consumption')
                ->first();

            if (! $sourceLedger || ! $item->product?->track_stock) {
                continue;
            }

            $quantity = (float) $item->quantity;
            $unitCost = (float) $sourceLedger->unit_cost;

            StockLedger::query()->create([
                'business_id' => $sale->business_id,
                'branch_id' => $sale->branch_id,
                'warehouse_id' => $sourceLedger->warehouse_id,
                'product_id' => $item->product_id,
                'unit_of_measure_id' => $sourceLedger->unit_of_measure_id,
                'uuid' => (string) Str::uuid(),
                'movement_type' => $movementType,
                'quantity_in' => $quantity,
                'quantity_out' => 0,
                'unit_cost' => $unitCost,
                'total_cost' => $quantity * $unitCost,
                'source_type' => Sale::class,
                'source_id' => $sale->id,
                'reference_number' => $sale->sale_number,
                'occurred_at' => now(),
                'created_by' => $userId,
            ]);

            $balance = StockBalance::query()->firstOrCreate(
                ['warehouse_id' => $sourceLedger->warehouse_id, 'product_id' => $item->product_id],
                [
                    'business_id' => $sale->business_id,
                    'branch_id' => $sale->branch_id,
                    'quantity_on_hand' => 0,
                    'average_cost' => $unitCost,
                    'stock_value' => 0,
                ],
            );

            $newQuantity = (float) $balance->quantity_on_hand + $quantity;
            $balance->update([
                'quantity_on_hand' => $newQuantity,
                'average_cost' => $unitCost,
                'stock_value' => round($newQuantity * $unitCost, 2),
            ]);
        }
    }

    private function appendStatusReason(?string $notes, string $label, ?string $reason): string
    {
        $line = $label.($reason ? ': '.$reason : '');

        return trim($notes ? $notes.PHP_EOL.$line : $line);
    }

    private function countDrawerCash(array $drawerCounts): float
    {
        return round(collect($drawerCounts)->sum(fn (array $count): float => (float) $count['denomination'] * (int) $count['quantity']), 2);
    }

    private function normalizeDrawerCounts(array $drawerCounts): array
    {
        return collect($drawerCounts)
            ->map(fn (array $count): array => [
                'denomination' => round((float) $count['denomination'], 2),
                'quantity' => (int) $count['quantity'],
                'subtotal' => round((float) $count['denomination'] * (int) $count['quantity'], 2),
                'label' => $count['label'] ?? null,
            ])
            ->values()
            ->all();
    }

    private function drawerAuditStatus(float $variance, bool $approved): string
    {
        if ($variance === 0.0) {
            return 'balanced';
        }

        return $approved ? 'variance_approved' : 'variance_pending';
    }

    private function recordSalesConsumption(int $businessId, int $branchId, int $warehouseId, Product $product, float $quantity, Sale $sale): void
    {
        if (! $product->track_stock) {
            return;
        }

        $unitCost = (float) $product->cost_price;

        StockLedger::query()->create([
            'business_id' => $businessId,
            'branch_id' => $branchId,
            'warehouse_id' => $warehouseId,
            'product_id' => $product->id,
            'unit_of_measure_id' => $product->unit_of_measure_id,
            'uuid' => (string) Str::uuid(),
            'movement_type' => 'sales_consumption',
            'quantity_in' => 0,
            'quantity_out' => $quantity,
            'unit_cost' => $unitCost,
            'total_cost' => $quantity * $unitCost,
            'source_type' => Sale::class,
            'source_id' => $sale->id,
            'reference_number' => $sale->sale_number,
            'occurred_at' => now(),
            'created_by' => $sale->cashier_id,
        ]);

        $balance = StockBalance::query()->firstOrCreate(
            ['warehouse_id' => $warehouseId, 'product_id' => $product->id],
            ['business_id' => $businessId, 'branch_id' => $branchId, 'quantity_on_hand' => 0, 'average_cost' => $unitCost, 'stock_value' => 0],
        );

        $newQuantity = max((float) $balance->quantity_on_hand - $quantity, 0);
        $newValue = max($newQuantity * (float) $balance->average_cost, 0);

        $balance->update([
            'quantity_on_hand' => $newQuantity,
            'stock_value' => round($newValue, 2),
        ]);
    }
}
