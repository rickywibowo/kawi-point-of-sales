<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $business = $request->attributes->get('business');
        $branch = $request->attributes->get('branch');

        abort_unless(
            $business && $request->user()?->canInTenant($permission, $business->id, $branch?->id),
            403,
            'Missing permission: '.$permission,
        );

        return $next($request);
    }
}
