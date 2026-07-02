<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\StoreSaleRequest;
use App\Http\Requests\Pos\SaleStatusRequest;
use App\Models\Sale;
use App\Services\Pos\PosService;
use App\Services\Pos\ReceiptService;
use Illuminate\Http\JsonResponse;

class SaleController extends Controller
{
    public function store(StoreSaleRequest $request, PosService $service): JsonResponse
    {
        $sale = $service->completeSale(
            $request->attributes->get('business'),
            $request->attributes->get('branch'),
            $request->validated(),
            $request,
        );

        return response()->json(['sale' => $sale], 201);
    }

    public function void(SaleStatusRequest $request, Sale $sale, PosService $service): JsonResponse
    {
        $sale = $service->voidSale(
            $request->attributes->get('business'),
            $request->attributes->get('branch'),
            $sale,
            $request->validated(),
            $request,
        );

        return response()->json(['sale' => $sale]);
    }

    public function refund(SaleStatusRequest $request, Sale $sale, PosService $service): JsonResponse
    {
        $sale = $service->refundSale(
            $request->attributes->get('business'),
            $request->attributes->get('branch'),
            $sale,
            $request->validated(),
            $request,
        );

        return response()->json(['sale' => $sale]);
    }

    public function receipt(Sale $sale, ReceiptService $receipts): JsonResponse
    {
        $receipt = $receipts->build(
            request()->attributes->get('business'),
            request()->attributes->get('branch')?->id,
            $sale,
        );

        return response()->json(['receipt' => $receipt]);
    }
}
