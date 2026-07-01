<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Offline\SyncOfflineSalesRequest;
use App\Models\OfflineSyncConflict;
use App\Services\Offline\OfflineSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OfflineSyncController extends Controller
{
    public function syncSales(SyncOfflineSalesRequest $request, OfflineSyncService $service): JsonResponse
    {
        $batch = $service->syncSales(
            $request->attributes->get('business'),
            $request->attributes->get('branch'),
            $request->validated(),
            $request,
        );

        return response()->json(['batch' => $batch]);
    }

    public function conflicts(Request $request): JsonResponse
    {
        $business = $request->attributes->get('business');
        $branch = $request->attributes->get('branch');

        return response()->json([
            'conflicts' => OfflineSyncConflict::query()
                ->forTenant($business->id, $branch?->id)
                ->latest()
                ->get(),
        ]);
    }
}
