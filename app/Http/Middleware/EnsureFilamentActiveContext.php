<?php

namespace App\Http\Middleware;

use App\Models\Branch;
use App\Models\Business;
use App\Services\Tenancy\ActiveContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFilamentActiveContext
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('admin/manage-active-context') || $request->is('admin/active-context') || $request->is('admin/logout')) {
            return $next($request);
        }

        $user = $request->user();
        $context = app(ActiveContext::class);
        $businessId = $context->businessId();
        $outletId = $context->outletId();

        if (! $user || ! $businessId || ! $outletId) {
            return redirect()->route('filament.admin.pages.manage-active-context');
        }

        $business = Business::query()->whereKey($businessId)->where('is_active', true)->first();
        $outlet = Branch::query()->whereKey($outletId)->where('business_id', $businessId)->where('is_active', true)->first();
        $hasAccess = $business
            && $outlet
            && $user->businesses()->whereKey($businessId)->exists()
            && $user->outlets()->whereKey($outletId)->exists();

        if (! $hasAccess) {
            $context->clear();

            return redirect()->route('filament.admin.pages.manage-active-context');
        }

        return $next($request);
    }
}
