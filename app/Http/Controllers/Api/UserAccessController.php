<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Administration\AssignRoleRequest;
use App\Http\Requests\Administration\InviteUserRequest;
use App\Models\User;
use App\Services\Administration\UserAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserAccessController extends Controller
{
    public function index(Request $request, UserAccessService $access): JsonResponse
    {
        return response()->json($access->directory($request->attributes->get('business')));
    }

    public function store(InviteUserRequest $request, UserAccessService $access): JsonResponse
    {
        $user = $access->inviteUser(
            $request->attributes->get('business'),
            $request->validated(),
            $request,
        );

        return response()->json(['user' => $user], 201);
    }

    public function assignRole(AssignRoleRequest $request, User $user, UserAccessService $access): JsonResponse
    {
        $user = $access->assignRole(
            $request->attributes->get('business'),
            $user,
            $request->validated(),
            $request,
        );

        return response()->json(['user' => $user]);
    }
}
