<?php

namespace App\Http\Middleware;

use App\Models\Business;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveBusinessContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $businessId = $request->session()->get('active_business_id');

        abort_if($businessId === null, 428, 'Active business context is required.');

        $business = Business::query()
            ->whereKey($businessId)
            ->where('is_active', true)
            ->first();

        abort_unless($business, 403, 'Active business context is invalid.');
        abort_unless($request->user()?->businesses()->whereKey($business->id)->exists(), 403, 'Active business context is not accessible.');

        $request->attributes->set('active_business', $business);

        return $next($request);
    }
}
