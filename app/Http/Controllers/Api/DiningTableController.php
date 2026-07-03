<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\StoreDiningTableRequest;
use App\Http\Requests\Pos\StoreTableReservationRequest;
use App\Http\Requests\Pos\UpdateDiningTableStatusRequest;
use App\Models\DiningTable;
use App\Models\TableReservation;
use App\Services\Pos\DiningTableService;
use Illuminate\Http\JsonResponse;

class DiningTableController extends Controller
{
    public function store(StoreDiningTableRequest $request, DiningTableService $tables): JsonResponse
    {
        $table = $tables->create(
            $request->attributes->get('business'),
            $request->attributes->get('branch'),
            $request->validated(),
            $request,
        );

        return response()->json(['dining_table' => $table], 201);
    }

    public function updateStatus(UpdateDiningTableStatusRequest $request, DiningTable $table, DiningTableService $tables): JsonResponse
    {
        $table = $tables->updateStatus(
            $request->attributes->get('business'),
            $request->attributes->get('branch'),
            $table,
            $request->validated('status'),
            $request,
        );

        return response()->json(['dining_table' => $table]);
    }

    public function reserve(StoreTableReservationRequest $request, DiningTable $table, DiningTableService $tables): JsonResponse
    {
        $reservation = $tables->reserve(
            $request->attributes->get('business'),
            $request->attributes->get('branch'),
            $table,
            $request->validated(),
            $request,
        );

        return response()->json(['table_reservation' => $reservation], 201);
    }

    public function cancelReservation(TableReservation $reservation, DiningTableService $tables): JsonResponse
    {
        $reservation = $tables->cancelReservation(
            request()->attributes->get('business'),
            request()->attributes->get('branch'),
            $reservation,
            request(),
        );

        return response()->json(['table_reservation' => $reservation]);
    }

    public function seatReservation(TableReservation $reservation, DiningTableService $tables): JsonResponse
    {
        $reservation = $tables->seatReservation(
            request()->attributes->get('business'),
            request()->attributes->get('branch'),
            $reservation,
            request(),
        );

        return response()->json(['table_reservation' => $reservation]);
    }
}
