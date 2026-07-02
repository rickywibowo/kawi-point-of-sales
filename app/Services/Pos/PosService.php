<?php

namespace App\Services\Pos;

use App\Models\Branch;
use App\Models\Business;
use App\Models\CashierShift;
use App\Models\Customer;
use App\Models\HeldTransaction;
use App\Models\Modifier;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockBalance;
use App\Models\StockLedger;
use App\Models\Warehouse;
use App\Services\Accounting\AccountingService;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PosService
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly AccountingService $accounting,
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

        $cashSales = Sale::query()
            ->where('cashier_shift_id', $shift->id)
            ->where('status', 'completed')
            ->whereHas('payments', fn ($query) => $query->where('method', 'cash'))
            ->with('payments')
            ->get()
            ->flatMap->payments
            ->where('method', 'cash')
            ->sum(fn ($payment) => (float) $payment->amount);

        $expectedCash = (float) $shift->opening_cash + $cashSales;
        $actualCash = (float) $data['actual_cash'];

        $shift->update([
            'expected_cash' => $expectedCash,
            'actual_cash' => $actualCash,
            'cash_difference' => $actualCash - $expectedCash,
            'status' => 'closed',
            'closed_at' => now(),
            'notes' => $data['notes'] ?? $shift->notes,
        ]);

        $this->audit->record('cashier_shift.closed', $shift, after: $shift->fresh()->toArray(), request: $request);

        return $shift->fresh();
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

        return DB::transaction(function () use ($business, $branch, $warehouse, $shift, $data, $request): Sale {
            [$items, $subtotal, $discountTotal, $taxTotal] = $this->prepareItems($business->id, $branch->id, $data['items']);
            $serviceChargeTotal = (float) ($data['service_charge_total'] ?? 0);
            $grandTotal = round($subtotal - $discountTotal + $taxTotal + $serviceChargeTotal, 2);
            $paidTotal = collect($data['payments'])->sum(fn ($payment) => (float) $payment['amount']);

            if ($paidTotal < $grandTotal) {
                throw ValidationException::withMessages(['payments' => ['Paid total must cover grand total.']]);
            }

            $sale = Sale::query()->create([
                'business_id' => $business->id,
                'branch_id' => $branch->id,
                'cashier_shift_id' => $shift->id,
                'customer_id' => $data['customer_id'] ?? null,
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

            $sale->load(['items.modifiers', 'payments']);
            $this->accounting->postSaleJournal($sale, $request);
            $this->audit->record('sale.completed', $sale, after: $sale->toArray(), request: $request);

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
