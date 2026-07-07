<?php

namespace App\Http\Middleware;

use App\Models\Branch;
use App\Models\Business;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveOutletContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $businessId = $request->session()->get('active_business_id');
        $outletId = $request->session()->get('active_outlet_id');

        abort_if($businessId === null || $outletId === null, 428, 'Active outlet context is required.');

        $business = Business::query()
            ->whereKey($businessId)
            ->where('is_active', true)
            ->first();
        $outlet = Branch::query()
            ->whereKey($outletId)
            ->where('business_id', $businessId)
            ->where('is_active', true)
            ->first();

        abort_unless($business && $outlet, 403, 'Active outlet context is invalid.');
        abort_unless($request->user()?->businesses()->whereKey($business->id)->exists(), 403, 'Active business context is not accessible.');
        abort_unless($request->user()?->outlets()->whereKey($outlet->id)->exists(), 403, 'Active outlet context is not accessible.');

        $request->attributes->set('active_business', $business);
        $request->attributes->set('active_outlet', $outlet);

        return $next($request);
    }
}
