<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\StoreDiningTableRequest;
use App\Http\Requests\Pos\UpdateDiningTableStatusRequest;
use App\Models\DiningTable;
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
}
