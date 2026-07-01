<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\StoreCategoryRequest;
use App\Services\MasterData\MasterDataService;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function store(StoreCategoryRequest $request, MasterDataService $service): JsonResponse
    {
        $category = $service->createCategory(
            $request->attributes->get('business'),
            $request->validated(),
            $request,
        );

        return response()->json(['category' => $category], 201);
    }
}
