<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\PostStockTransferRequest;
use App\Services\Inventory\InventoryService;
use Illuminate\Http\JsonResponse;

class StockTransferController extends Controller
{
    public function store(PostStockTransferRequest $request, InventoryService $service): JsonResponse
    {
        $transfer = $service->postTransfer(
            $request->attributes->get('business'),
            $request->validated(),
            $request,
        );

        return response()->json(['transfer' => $transfer], 201);
    }
}
