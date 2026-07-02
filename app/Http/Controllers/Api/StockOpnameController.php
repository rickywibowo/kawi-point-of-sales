<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\PostStockOpnameRequest;
use App\Services\Inventory\InventoryService;
use Illuminate\Http\JsonResponse;

class StockOpnameController extends Controller
{
    public function store(PostStockOpnameRequest $request, InventoryService $service): JsonResponse
    {
        $opname = $service->postOpname(
            $request->attributes->get('business'),
            $request->validated(),
            $request,
        );

        return response()->json(['opname' => $opname], 201);
    }
}
