<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\Audit\AuditLogger;
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
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $this->serializeUser($request->user()),
            'business' => $request->attributes->get('business'),
            'branch' => $request->attributes->get('branch'),
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
            'businesses' => $user->businesses,
            'roles' => $user->roles,
        ];
    }
}
