<?php

use App\Models\Branch;
use App\Models\Business;
use App\Services\Audit\AuditLogger;
use App\Services\Tenancy\FilamentActiveContextManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/admin/context', function (Request $request, AuditLogger $audit) {
    return redirect()->route('filament.admin.pages.manage-active-context');
})->middleware(['web', 'auth'])->name('filament.context.switch');

Route::post('/admin/header-active-context', function (Request $request, FilamentActiveContextManager $context) {
    $data = $request->validate([
        'business_id' => ['required', 'integer'],
        'outlet_id' => ['nullable', 'integer'],
    ]);

    $businessId = (int) $data['business_id'];
    $outletId = isset($data['outlet_id']) ? (int) $data['outlet_id'] : null;

    if ($outletId) {
        $context->switchOutlet($request->user(), $businessId, $outletId);
    } else {
        $context->switchBusiness($request->user(), $businessId);
    }

    return back();
})->middleware(['web', 'auth'])->name('filament.active-context.header-switch');

Route::post('/admin/active-context', function (Request $request, AuditLogger $audit) {
    $request->validate([
        'business_id' => ['required', 'integer'],
        'outlet_id' => ['required', 'integer'],
    ]);

    $businessId = (int) $request->integer('business_id');
    $branchId = (int) $request->integer('outlet_id');
    $user = $request->user();
    $business = Business::query()->whereKey($businessId)->where('is_active', true)->first();
    $branch = Branch::query()->where('business_id', $businessId)->whereKey($branchId)->where('is_active', true)->first();

    abort_unless($user && $business && $branch, 422, 'Invalid context.');
    abort_unless($user->businesses()->whereKey($businessId)->exists(), 403, 'Selected business is not accessible.');
    abort_unless($user->outlets()->whereKey($branchId)->exists(), 403, 'Selected outlet is not accessible.');

    $request->session()->put('active_business_id', $businessId);
    $request->session()->put('active_outlet_id', $branchId);

    $audit->record('auth.context_switched', $user, after: [
        'business_id' => $businessId,
        'outlet_id' => $branchId,
    ], request: $request);

    return redirect()->route('filament.admin.pages.manage-active-context')->with('status', 'Active context berhasil diganti.');
})->middleware(['web', 'auth'])->name('filament.active-context.switch');
