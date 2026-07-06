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
            $request->attributes->get('branch'),
            $request->validated(),
            $request,
        );

        return response()->json(['category' => $category], 201);
    }

    public function destroy(\Illuminate\Http\Request $request, MasterDataService $service, int $category): JsonResponse
    {
        $service->deleteCategory(
            $request->attributes->get('business'),
            $request->attributes->get('branch'),
            $category,
            $request,
        );

        return response()->json(status: 204);
    }
}
