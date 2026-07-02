<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Recipe;
use App\Models\StockBalance;
use App\Models\StockLedger;
use App\Models\StockOpname;
use App\Models\StockTransfer;
use App\Models\Warehouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $business = $request->attributes->get('business');
        $branch = $request->attributes->get('branch');

        return response()->json([
            'warehouses' => Warehouse::query()->forTenant($business->id, $branch?->id)->orderBy('name')->get(),
            'stock_balances' => StockBalance::query()
                ->forTenant($business->id, $branch?->id)
                ->with(['warehouse', 'product'])
                ->orderBy('warehouse_id')
                ->get(),
            'stock_ledgers' => StockLedger::query()
                ->forTenant($business->id, $branch?->id)
                ->with(['warehouse', 'product'])
                ->latest('occurred_at')
                ->limit(25)
                ->get(),
            'recipes' => Recipe::query()
                ->forBusiness($business->id)
                ->with(['product', 'items.ingredientProduct'])
                ->orderBy('name')
                ->get(),
            'stock_transfers' => StockTransfer::query()
                ->forBusiness($business->id)
                ->with('items.product')
                ->latest()
                ->limit(20)
                ->get(),
            'stock_opnames' => StockOpname::query()
                ->forTenant($business->id, $branch?->id)
                ->with('items.product')
                ->latest()
                ->limit(20)
                ->get(),
        ]);
    }
}
