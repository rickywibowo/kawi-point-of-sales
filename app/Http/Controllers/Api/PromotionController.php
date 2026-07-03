<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\StorePromotionRequest;
use App\Services\Pos\PromotionService;
use Illuminate\Http\JsonResponse;

class PromotionController extends Controller
{
    public function store(StorePromotionRequest $request, PromotionService $promotions): JsonResponse
    {
        $promotion = $promotions->create(
            $request->attributes->get('business'),
            $request->validated(),
            $request,
        );

        return response()->json(['promotion' => $promotion], 201);
    }
}
