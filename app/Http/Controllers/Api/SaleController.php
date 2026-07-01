<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\StoreSaleRequest;
use App\Services\Pos\PosService;
use Illuminate\Http\JsonResponse;

class SaleController extends Controller
{
    public function store(StoreSaleRequest $request, PosService $service): JsonResponse
    {
        $sale = $service->completeSale(
            $request->attributes->get('business'),
            $request->attributes->get('branch'),
            $request->validated(),
            $request,
        );

        return response()->json(['sale' => $sale], 201);
    }
}
