<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreRecipeRequest;
use App\Services\Inventory\InventoryService;
use Illuminate\Http\JsonResponse;

class RecipeController extends Controller
{
    public function store(StoreRecipeRequest $request, InventoryService $service): JsonResponse
    {
        $recipe = $service->createRecipe(
            $request->attributes->get('business'),
            $request->validated(),
            $request,
        );

        return response()->json(['recipe' => $recipe], 201);
    }
}
