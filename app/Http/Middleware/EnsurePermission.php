<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePermission
{
    private const ALIASES = [
        'sales.create' => 'manage sales',
        'sales.discount' => 'manage sales',
        'sales.void' => 'manage sales',
        'sales.refund' => 'manage sales',
        'inventory.view' => 'manage inventory',
        'inventory.adjust' => 'manage inventory',
        'purchases.manage' => 'manage inventory',
        'reports.view' => 'view report',
        'accounting.manage' => 'manage expense',
        'users.manage' => 'manage user',
    ];

    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $business = $request->attributes->get('business');
        $branch = $request->attributes->get('branch');
        $user = $request->user();
        $permissionNames = array_values(array_unique([
            $permission,
            self::ALIASES[$permission] ?? $permission,
        ]));

        abort_unless(
            $business && collect($permissionNames)->contains(
                fn (string $permissionName): bool => (bool) $user?->canInTenant($permissionName, $business->id, $branch?->id),
            ),
            403,
            'Missing permission: '.$permission,
        );

        return $next($request);
    }
}
