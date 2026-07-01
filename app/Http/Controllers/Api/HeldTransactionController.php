<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\HoldTransactionRequest;
use App\Services\Pos\PosService;
use Illuminate\Http\JsonResponse;

class HeldTransactionController extends Controller
{
    public function store(HoldTransactionRequest $request, PosService $service): JsonResponse
    {
        $held = $service->holdTransaction(
            $request->attributes->get('business'),
            $request->attributes->get('branch'),
            $request->validated(),
            $request,
        );

        return response()->json(['held_transaction' => $held], 201);
    }
}
