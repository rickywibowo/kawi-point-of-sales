<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\CloseShiftRequest;
use App\Http\Requests\Pos\OpenShiftRequest;
use App\Models\CashierShift;
use App\Services\Pos\PosService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CashierShiftController extends Controller
{
    public function store(OpenShiftRequest $request, PosService $service): JsonResponse
    {
        $shift = $service->openShift(
            $request->attributes->get('business'),
            $request->attributes->get('branch'),
            $request->validated(),
            $request,
        );

        return response()->json(['shift' => $shift], 201);
    }

    public function close(CloseShiftRequest $request, CashierShift $shift, PosService $service): JsonResponse
    {
        $closed = $service->closeShift(
            $request->attributes->get('business'),
            $request->attributes->get('branch'),
            $shift,
            $request->validated(),
            $request,
        );

        return response()->json(['shift' => $closed]);
    }
}
