<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Audit\AuditReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request, AuditReviewService $audit): JsonResponse
    {
        $business = $request->attributes->get('business');
        $branch = $request->attributes->get('branch');
        $filters = $request->validate([
            'action' => ['nullable', 'string', 'max:120'],
            'entity_type' => ['nullable', 'string', 'max:255'],
            'user_id' => ['nullable', 'integer'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        return response()->json([
            'summary' => $audit->summary($business, $branch?->id, $filters),
            'audit_logs' => $audit->logs($business, $branch?->id, $filters),
        ]);
    }
}
