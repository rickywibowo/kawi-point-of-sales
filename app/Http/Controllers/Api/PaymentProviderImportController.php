<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Accounting\StorePaymentProviderImportRequest;
use App\Models\PaymentProviderImport;
use App\Services\Accounting\PaymentProviderImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentProviderImportController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $business = $request->attributes->get('business');
        $branch = $request->attributes->get('branch');

        return response()->json([
            'payment_provider_imports' => PaymentProviderImport::query()
                ->forTenant($business->id, $branch?->id)
                ->with(['settlement', 'rows'])
                ->latest('imported_at')
                ->limit(50)
                ->get(),
        ]);
    }

    public function store(StorePaymentProviderImportRequest $request, PaymentProviderImportService $imports): JsonResponse
    {
        $providerImport = $imports->create(
            $request->attributes->get('business'),
            $request->attributes->get('branch')?->id,
            $request->validated(),
            $request,
        );

        return response()->json(['payment_provider_import' => $providerImport], 201);
    }
}
