<?php

use Illuminate\Support\Facades\Route;
use App\Models\Branch;
use App\Models\Business;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\Request;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/admin/context', function (Request $request, AuditLogger $audit) {
    $request->validate([
        'context' => ['required', 'string'],
    ]);

    $parts = explode(':', $request->string('context')->toString(), 2);
    abort_unless(count($parts) === 2, 422, 'Invalid context.');

    $businessId = (int) $parts[0];
    $branchId = (int) $parts[1];
    $user = $request->user();
    $business = Business::query()->whereKey($businessId)->where('is_active', true)->first();
    $branch = Branch::query()->where('business_id', $businessId)->whereKey($branchId)->where('is_active', true)->first();

    abort_unless($user && $business && $branch, 422, 'Invalid context.');
    abort_unless($user->canAccessBranchContext($businessId, $branchId), 403, 'Selected context is not accessible.');

    $user->forceFill([
        'current_business_id' => $businessId,
        'current_branch_id' => $branchId,
    ])->save();

    $audit->record('auth.context_switched', $user, after: [
        'business_id' => $businessId,
        'branch_id' => $branchId,
    ], request: $request);

    return redirect()->route('filament.admin.pages.context')->with('status', 'Context berhasil diganti.');
})->middleware(['web', 'auth'])->name('filament.context.switch');
