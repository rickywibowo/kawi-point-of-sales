<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Reports\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request, ReportService $reports): JsonResponse
    {
        $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ]);

        return response()->json([
            'reports' => $reports->dashboard(
                $request->attributes->get('business'),
                $request->attributes->get('branch')?->id,
                $request->query('date_from'),
                $request->query('date_to'),
            ),
        ]);
    }
}
