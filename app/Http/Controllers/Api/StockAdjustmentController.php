<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\PostStockAdjustmentRequest;
use App\Services\Inventory\InventoryService;
use Illuminate\Http\JsonResponse;

class StockAdjustmentController extends Controller
{
    public function store(PostStockAdjustmentRequest $request, InventoryService $service): JsonResponse
    {
        $adjustment = $service->postAdjustment(
            $request->attributes->get('business'),
            $request->attributes->get('branch')?->id,
            $request->validated(),
            $request,
        );

        return response()->json(['adjustment' => $adjustment], 201);
    }
}
