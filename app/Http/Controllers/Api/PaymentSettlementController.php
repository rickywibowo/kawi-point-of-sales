<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Accounting\StorePaymentSettlementRequest;
use App\Models\PaymentSettlement;
use App\Services\Accounting\PaymentSettlementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentSettlementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $business = $request->attributes->get('business');
        $branch = $request->attributes->get('branch');

        return response()->json([
            'payment_settlements' => PaymentSettlement::query()
                ->forTenant($business->id, $branch?->id)
                ->with('items')
                ->latest('posted_at')
                ->limit(50)
                ->get(),
        ]);
    }

    public function store(StorePaymentSettlementRequest $request, PaymentSettlementService $settlements): JsonResponse
    {
        $settlement = $settlements->create(
            $request->attributes->get('business'),
            $request->attributes->get('branch')?->id,
            $request->validated(),
            $request,
        );

        return response()->json(['payment_settlement' => $settlement], 201);
    }
}
