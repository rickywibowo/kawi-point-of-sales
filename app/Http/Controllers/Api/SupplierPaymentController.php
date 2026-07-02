<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Purchasing\PostSupplierPaymentRequest;
use App\Models\SupplierPayable;
use App\Services\Purchasing\PurchasingService;
use Illuminate\Http\JsonResponse;

class SupplierPaymentController extends Controller
{
    public function store(PostSupplierPaymentRequest $request, SupplierPayable $payable, PurchasingService $service): JsonResponse
    {
        $payment = $service->postSupplierPayment(
            $request->attributes->get('business'),
            $request->attributes->get('branch')?->id,
            $payable,
            $request->validated(),
            $request,
        );

        return response()->json(['supplier_payment' => $payment], 201);
    }
}
