<?php

namespace App\Services\Reports;

use App\Models\Business;
use App\Models\GoodsReceipt;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockBalance;
use App\Models\StockLedger;
use App\Models\SupplierPayable;
use App\Services\Accounting\AccountingService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function __construct(private readonly AccountingService $accounting)
    {
    }

    public function dashboard(Business $business, ?int $branchId = null, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $from = $dateFrom ? Carbon::parse($dateFrom)->startOfDay() : now()->startOfMonth();
        $to = $dateTo ? Carbon::parse($dateTo)->endOfDay() : now()->endOfDay();

        return [
            'period' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
            'sales' => $this->salesSummary($business->id, $branchId, $from, $to),
            'sales_by_branch' => $this->salesByBranch($business->id, $from, $to),
            'sales_by_product' => $this->salesByProduct($business->id, $branchId, $from, $to),
            'payment_methods' => $this->paymentMethods($business->id, $branchId, $from, $to),
            'stock' => $this->stockSummary($business->id, $branchId),
            'stock_movements' => $this->stockMovements($business->id, $branchId, $from, $to),
            'purchasing' => $this->purchasingSummary($business->id, $branchId, $from, $to),
            'accounting' => [
                'trial_balance' => $this->accounting->trialBalance($business->id),
                'profit_and_loss' => $this->accounting->profitAndLoss($business->id),
                'balance_sheet' => $this->accounting->balanceSheet($business->id),
                'cash_flow' => $this->accounting->cashFlow($business->id, $from->toDateString(), $to->toDateString()),
            ],
        ];
    }

    public function salesSummary(int $businessId, ?int $branchId, Carbon $from, Carbon $to): array
    {
        $query = Sale::query()
            ->where('business_id', $businessId)
            ->where('status', 'completed')
            ->whereBetween('sold_at', [$from, $to]);

        $this->applyBranch($query, $branchId);

        return [
            'transaction_count' => (clone $query)->count(),
            'subtotal' => round((float) (clone $query)->sum('subtotal'), 2),
            'discount_total' => round((float) (clone $query)->sum('discount_total'), 2),
            'tax_total' => round((float) (clone $query)->sum('tax_total'), 2),
            'service_charge_total' => round((float) (clone $query)->sum('service_charge_total'), 2),
            'grand_total' => round((float) (clone $query)->sum('grand_total'), 2),
            'refund_total' => round((float) Sale::query()
                ->where('business_id', $businessId)
                ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
                ->where('status', 'refunded')
                ->whereBetween('refunded_at', [$from, $to])
                ->sum('grand_total'), 2),
            'void_count' => Sale::query()
                ->where('business_id', $businessId)
                ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
                ->where('status', 'voided')
                ->whereBetween('voided_at', [$from, $to])
                ->count(),
        ];
    }

    public function salesByBranch(int $businessId, Carbon $from, Carbon $to): Collection
    {
        return Sale::query()
            ->select('branches.name as branch_name', DB::raw('count(sales.id) as transaction_count'), DB::raw('sum(sales.grand_total) as grand_total'))
            ->join('branches', 'branches.id', '=', 'sales.branch_id')
            ->where('sales.business_id', $businessId)
            ->where('sales.status', 'completed')
            ->whereBetween('sales.sold_at', [$from, $to])
            ->groupBy('branches.name')
            ->orderByDesc('grand_total')
            ->get();
    }

    public function salesByProduct(int $businessId, ?int $branchId, Carbon $from, Carbon $to): Collection
    {
        return SaleItem::query()
            ->select('sale_items.product_name', DB::raw('sum(sale_items.quantity) as quantity'), DB::raw('sum(sale_items.line_total) as sales_total'))
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sale_items.business_id', $businessId)
            ->where('sales.status', 'completed')
            ->whereBetween('sales.sold_at', [$from, $to])
            ->when($branchId, fn ($query) => $query->where('sale_items.branch_id', $branchId))
            ->groupBy('sale_items.product_name')
            ->orderByDesc('sales_total')
            ->get();
    }

    public function paymentMethods(int $businessId, ?int $branchId, Carbon $from, Carbon $to): Collection
    {
        return DB::table('sale_payments')
            ->select('sale_payments.method', DB::raw('count(sale_payments.id) as payment_count'), DB::raw('sum(sale_payments.amount) as amount'))
            ->join('sales', 'sales.id', '=', 'sale_payments.sale_id')
            ->where('sale_payments.business_id', $businessId)
            ->where('sales.status', 'completed')
            ->whereBetween('sales.sold_at', [$from, $to])
            ->when($branchId, fn ($query) => $query->where('sale_payments.branch_id', $branchId))
            ->groupBy('sale_payments.method')
            ->orderByDesc('amount')
            ->get();
    }

    public function stockSummary(int $businessId, ?int $branchId): array
    {
        $balances = StockBalance::query()
            ->where('business_id', $businessId)
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId));

        $lowStockProducts = Product::query()
            ->where('business_id', $businessId)
            ->where('track_stock', true)
            ->whereHas('branchPrices')
            ->count();

        return [
            'sku_count' => (clone $balances)->count(),
            'quantity_on_hand' => round((float) (clone $balances)->sum('quantity_on_hand'), 6),
            'stock_value' => round((float) (clone $balances)->sum('stock_value'), 2),
            'minimum_stock_alerts' => $lowStockProducts === 0 ? 0 : (clone $balances)->where('quantity_on_hand', '<=', 5)->count(),
            'slow_moving' => [],
            'fast_moving' => $this->fastMovingProducts($businessId, $branchId),
        ];
    }

    public function stockMovements(int $businessId, ?int $branchId, Carbon $from, Carbon $to): Collection
    {
        return StockLedger::query()
            ->select('movement_type', DB::raw('sum(quantity_in) as quantity_in'), DB::raw('sum(quantity_out) as quantity_out'), DB::raw('sum(total_cost) as total_cost'))
            ->where('business_id', $businessId)
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->whereBetween('occurred_at', [$from, $to])
            ->groupBy('movement_type')
            ->orderBy('movement_type')
            ->get();
    }

    public function purchasingSummary(int $businessId, ?int $branchId, Carbon $from, Carbon $to): array
    {
        return [
            'purchase_order_count' => PurchaseOrder::query()
                ->where('business_id', $businessId)
                ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
                ->whereBetween('order_date', [$from->toDateString(), $to->toDateString()])
                ->count(),
            'goods_receipt_total' => round((float) GoodsReceipt::query()
                ->where('business_id', $businessId)
                ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
                ->whereBetween('received_date', [$from->toDateString(), $to->toDateString()])
                ->sum('grand_total'), 2),
            'open_payable_total' => round((float) SupplierPayable::query()
                ->where('business_id', $businessId)
                ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
                ->where('status', 'open')
                ->sum(DB::raw('amount - paid_amount')), 2),
        ];
    }

    private function fastMovingProducts(int $businessId, ?int $branchId): Collection
    {
        return StockLedger::query()
            ->select('products.name', DB::raw('sum(stock_ledgers.quantity_out) as quantity_out'))
            ->join('products', 'products.id', '=', 'stock_ledgers.product_id')
            ->where('stock_ledgers.business_id', $businessId)
            ->when($branchId, fn ($query) => $query->where('stock_ledgers.branch_id', $branchId))
            ->where('stock_ledgers.movement_type', 'sales_consumption')
            ->groupBy('products.name')
            ->orderByDesc('quantity_out')
            ->limit(5)
            ->get();
    }

    private function applyBranch($query, ?int $branchId): void
    {
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
    }
}
