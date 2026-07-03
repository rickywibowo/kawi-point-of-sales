<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CashDrawerAudit;
use App\Models\DeliveryOrder;
use App\Models\DiningTable;
use App\Models\HeldTransaction;
use App\Models\KitchenTicket;
use App\Models\KitchenStation;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Sale;
use App\Models\TableReservation;
use App\Models\Warehouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PosController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $business = $request->attributes->get('business');
        $branch = $request->attributes->get('branch');

        abort_unless($branch, 422, 'Branch context is required for POS.');

        return response()->json([
            'products' => Product::query()
                ->forBusiness($business->id)
                ->with(['category', 'tax', 'branchPrices', 'modifierGroups.modifiers'])
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'warehouses' => Warehouse::query()->forTenant($business->id, $branch->id)->where('is_active', true)->get(),
            'promotions' => Promotion::query()->forBusiness($business->id)->where('is_active', true)->orderBy('code')->get(),
            'dining_tables' => DiningTable::query()->forTenant($business->id, $branch->id)->orderBy('section')->orderBy('code')->get(),
            'table_reservations' => TableReservation::query()
                ->forTenant($business->id, $branch->id)
                ->with(['diningTable', 'customer'])
                ->whereIn('status', ['booked', 'seated'])
                ->whereDate('reserved_at', now()->toDateString())
                ->orderBy('reserved_at')
                ->get(),
            'held_transactions' => HeldTransaction::query()
                ->forTenant($business->id, $branch->id)
                ->whereNull('resumed_at')
                ->latest('held_at')
                ->get(),
            'kitchen_tickets' => KitchenTicket::query()
                ->forTenant($business->id, $branch->id)
                ->with(['items', 'diningTable'])
                ->whereIn('status', ['open', 'preparing', 'ready'])
                ->latest('opened_at')
                ->get(),
            'kitchen_stations' => KitchenStation::query()
                ->forTenant($business->id, $branch->id)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
            'delivery_orders' => DeliveryOrder::query()
                ->forTenant($business->id, $branch->id)
                ->with('sale')
                ->whereIn('status', ['pending', 'assigned', 'picked_up'])
                ->latest()
                ->get(),
            'cash_drawer_audits' => CashDrawerAudit::query()
                ->forTenant($business->id, $branch->id)
                ->with('cashierShift')
                ->latest('audited_at')
                ->limit(10)
                ->get(),
            'today_sales' => Sale::query()
                ->forTenant($business->id, $branch->id)
                ->with('payments')
                ->whereDate('sold_at', now()->toDateString())
                ->latest('sold_at')
                ->limit(20)
                ->get(),
        ]);
    }
}
