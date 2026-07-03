<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\UpdateDeliveryStatusRequest;
use App\Models\DeliveryOrder;
use App\Services\Pos\DeliveryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeliveryOrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $business = $request->attributes->get('business');
        $branch = $request->attributes->get('branch');

        return response()->json([
            'delivery_orders' => DeliveryOrder::query()
                ->forTenant($business->id, $branch?->id)
                ->with('sale')
                ->whereIn('status', ['pending', 'assigned', 'picked_up'])
                ->latest()
                ->get(),
        ]);
    }

    public function updateStatus(UpdateDeliveryStatusRequest $request, DeliveryOrder $delivery, DeliveryService $deliveries): JsonResponse
    {
        $delivery = $deliveries->updateStatus(
            $request->attributes->get('business'),
            $request->attributes->get('branch')?->id,
            $delivery,
            $request->validated(),
            $request,
        );

        return response()->json(['delivery_order' => $delivery]);
    }
}
