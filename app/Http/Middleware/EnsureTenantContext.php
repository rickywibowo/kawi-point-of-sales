<?php

namespace App\Http\Middleware;

use App\Services\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantContext
{
    public function __construct(private readonly TenantContext $tenancy)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        [$business, $branch] = $this->tenancy->resolve(
            $request->user(),
            $request->header('X-Business-Id'),
            $request->header('X-Branch-Id'),
        );

        $request->attributes->set('business', $business);
        $request->attributes->set('branch', $branch);

        return $next($request);
    }
}
