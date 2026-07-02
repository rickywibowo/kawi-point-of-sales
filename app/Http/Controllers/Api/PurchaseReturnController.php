<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Purchasing\PostPurchaseReturnRequest;
use App\Services\Purchasing\PurchasingService;
use Illuminate\Http\JsonResponse;

class PurchaseReturnController extends Controller
{
    public function store(PostPurchaseReturnRequest $request, PurchasingService $service): JsonResponse
    {
        $return = $service->postPurchaseReturn(
            $request->attributes->get('business'),
            $request->attributes->get('branch')?->id,
            $request->validated(),
            $request,
        );

        return response()->json(['purchase_return' => $return], 201);
    }
}
