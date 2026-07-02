<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Accounting\StoreOperationalExpenseRequest;
use App\Services\Accounting\OperationalExpenseService;
use Illuminate\Http\JsonResponse;

class OperationalExpenseController extends Controller
{
    public function store(StoreOperationalExpenseRequest $request, OperationalExpenseService $expenses): JsonResponse
    {
        $expense = $expenses->post(
            $request->attributes->get('business'),
            $request->attributes->get('branch')?->id,
            $request->validated(),
            $request,
        );

        return response()->json(['operational_expense' => $expense], 201);
    }
}
