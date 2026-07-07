<?php

namespace App\Http\Middleware;

use App\Services\Tenancy\FilamentActiveContextManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFilamentActiveContext
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('admin/manage-active-context') || $request->is('admin/active-context') || $request->is('admin/header-active-context') || $request->is('admin/logout')) {
            return $next($request);
        }

        $user = $request->user();
        $context = app(FilamentActiveContextManager::class);

        if (! $user || ! $context->ensureValidOrAutoSelect($user)) {
            return redirect()->route('filament.admin.pages.manage-active-context');
        }

        return $next($request);
    }
}
