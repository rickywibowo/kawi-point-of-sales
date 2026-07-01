<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Purchasing\StorePurchaseOrderRequest;
use App\Models\PurchaseOrder;
use App\Services\Purchasing\PurchasingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PurchaseOrderController extends Controller
{
    public function store(StorePurchaseOrderRequest $request, PurchasingService $service): JsonResponse
    {
        $po = $service->createPurchaseOrder(
            $request->attributes->get('business'),
            $request->attributes->get('branch')?->id,
            $request->validated(),
            $request,
        );

        return response()->json(['purchase_order' => $po], 201);
    }

    public function approve(Request $request, PurchaseOrder $purchaseOrder, PurchasingService $service): JsonResponse
    {
        $po = $service->approvePurchaseOrder($request->attributes->get('business'), $purchaseOrder, $request);

        return response()->json(['purchase_order' => $po]);
    }
}
