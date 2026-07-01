<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Purchasing\PostGoodsReceiptRequest;
use App\Services\Purchasing\PurchasingService;
use Illuminate\Http\JsonResponse;

class GoodsReceiptController extends Controller
{
    public function store(PostGoodsReceiptRequest $request, PurchasingService $service): JsonResponse
    {
        $receipt = $service->postGoodsReceipt(
            $request->attributes->get('business'),
            $request->attributes->get('branch')?->id,
            $request->validated(),
            $request,
        );

        return response()->json(['goods_receipt' => $receipt], 201);
    }
}
