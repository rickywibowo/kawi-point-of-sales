<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\SwitchContextRequest;
use App\Models\Branch;
use App\Models\Business;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\UserContextOptions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(LoginRequest $request, AuditLogger $audit): JsonResponse
    {
        $user = User::query()->where('email', $request->validated('email'))->first();

        if (! $user || ! Hash::check($request->validated('password'), $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken($request->validated('device_name') ?? 'kawi-pos')->plainTextToken;

        $audit->record('login', $user, request: $request);

        return response()->json([
            'token' => $token,
            'user' => $this->serializeUser($user),
            'contexts' => UserContextOptions::forUser($user),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $this->serializeUser($request->user()),
            'business' => $request->attributes->get('business'),
            'branch' => $request->attributes->get('branch'),
            'contexts' => UserContextOptions::forUser($request->user()),
        ]);
    }

    public function contexts(Request $request): JsonResponse
    {
        return response()->json([
            'contexts' => UserContextOptions::forUser($request->user()),
        ]);
    }

    public function switchContext(SwitchContextRequest $request, AuditLogger $audit): JsonResponse
    {
        $user = $request->user();
        $businessId = (int) $request->validated('business_id');
        $branchId = $request->validated('branch_id') !== null ? (int) $request->validated('branch_id') : null;

        $business = Business::query()->whereKey($businessId)->where('is_active', true)->first();
        $branch = $branchId
            ? Branch::query()->where('business_id', $businessId)->whereKey($branchId)->where('is_active', true)->first()
            : null;

        abort_unless($business && ($branchId === null || $branch), 422, 'Selected context is invalid.');
        abort_unless($user->canAccessBranchContext($businessId, $branchId), 403, 'Selected context is not accessible.');

        $user->forceFill([
            'current_business_id' => $businessId,
            'current_branch_id' => $branchId,
        ])->save();

        $audit->record('auth.context_switched', $user, after: [
            'business_id' => $businessId,
            'branch_id' => $branchId,
        ], request: $request);

        return response()->json([
            'user' => $this->serializeUser($user->fresh()),
            'business' => $business,
            'branch' => $branch,
            'contexts' => UserContextOptions::forUser($user->fresh()),
        ]);
    }

    public function logout(Request $request, AuditLogger $audit): JsonResponse
    {
        $audit->record('logout', $request->user(), request: $request);
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Logged out.']);
    }

    private function serializeUser(User $user): array
    {
        $user->loadMissing('businesses.branches', 'roles.permissions');

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'current_business_id' => $user->current_business_id,
            'current_branch_id' => $user->current_branch_id,
            'businesses' => $user->businesses,
            'roles' => $user->roles,
        ];
    }
}
