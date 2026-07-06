<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\StoreProductRequest;
use App\Services\MasterData\MasterDataService;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    public function store(StoreProductRequest $request, MasterDataService $service): JsonResponse
    {
        $product = $service->createProduct(
            $request->attributes->get('business'),
            $request->attributes->get('branch'),
            $request->validated(),
            $request,
        );

        return response()->json(['product' => $product], 201);
    }
}
