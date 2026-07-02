<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use App\Models\PurchaseReturn;
use App\Models\SupplierPayable;
use App\Models\SupplierPayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PurchasingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $business = $request->attributes->get('business');
        $branch = $request->attributes->get('branch');

        return response()->json([
            'purchase_orders' => PurchaseOrder::query()->forTenant($business->id, $branch?->id)->with(['supplier', 'warehouse', 'items.product'])->latest()->get(),
            'goods_receipts' => GoodsReceipt::query()->forTenant($business->id, $branch?->id)->with(['supplier', 'warehouse', 'items.product'])->latest()->get(),
            'purchase_returns' => PurchaseReturn::query()->forTenant($business->id, $branch?->id)->with('items.product')->latest()->get(),
            'supplier_payables' => SupplierPayable::query()->forTenant($business->id, $branch?->id)->with('payments')->latest()->get(),
            'supplier_payments' => SupplierPayment::query()->forTenant($business->id, $branch?->id)->with(['supplier', 'payable'])->latest('payment_date')->get(),
        ]);
    }
}
