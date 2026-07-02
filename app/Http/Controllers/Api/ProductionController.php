<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Production\PostProductionOrderRequest;
use App\Services\Production\ProductionService;
use Illuminate\Http\JsonResponse;

class ProductionController extends Controller
{
    public function store(PostProductionOrderRequest $request, ProductionService $service): JsonResponse
    {
        $production = $service->postProduction(
            $request->attributes->get('business'),
            $request->validated(),
            $request,
        );

        return response()->json(['production_order' => $production], 201);
    }
}
