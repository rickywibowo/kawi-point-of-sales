<?php

namespace App\Services\Administration;

use App\Models\Branch;
use App\Models\Business;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UserAccessService
{
    public function __construct(private readonly AuditLogger $audit)
    {
    }

    public function directory(Business $business): array
    {
        return [
            'users' => User::query()
                ->whereHas('businesses', fn ($query) => $query->whereKey($business->id))
                ->with([
                    'businesses' => fn ($query) => $query->whereKey($business->id),
                    'roles' => fn ($query) => $query->wherePivot('business_id', $business->id)->with('permissions'),
                ])
                ->orderBy('name')
                ->get(),
            'roles' => Role::query()
                ->where(function ($query) use ($business): void {
                    $query->whereNull('business_id')->orWhere('business_id', $business->id);
                })
                ->with('permissions')
                ->orderBy('name')
                ->get(),
            'permissions' => Permission::query()->orderBy('name')->get(),
            'branches' => Branch::query()->where('business_id', $business->id)->orderBy('name')->get(),
        ];
    }

    public function inviteUser(Business $business, array $data, Request $request): User
    {
        return DB::transaction(function () use ($business, $data, $request): User {
            $user = User::query()->firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make($data['password'] ?? Str::random(24)),
                    'current_business_id' => $business->id,
                    'current_branch_id' => $data['branch_id'] ?? null,
                ],
            );

            if (! $user->wasRecentlyCreated) {
                $user->fill([
                    'name' => $data['name'] ?? $user->name,
                    'current_business_id' => $user->current_business_id ?? $business->id,
                    'current_branch_id' => $data['branch_id'] ?? $user->current_branch_id,
                ])->save();
            }

            $this->assertBranch($business->id, $data['branch_id'] ?? null);
            $user->businesses()->syncWithoutDetaching([
                $business->id => ['is_owner' => $data['is_owner'] ?? false],
            ]);

            foreach ($data['roles'] ?? [] as $roleAssignment) {
                $this->assignRole($business, $user, $roleAssignment, $request, recordAudit: false);
            }

            $user->load('businesses', 'roles.permissions');
            $this->audit->record('user.invited', $user, after: $user->toArray(), request: $request);

            return $user;
        });
    }

    public function assignRole(Business $business, User $user, array $data, Request $request, bool $recordAudit = true): User
    {
        if (! $user->belongsToBusiness($business->id)) {
            throw ValidationException::withMessages(['user_id' => ['The selected user is outside the active business.']]);
        }

        $role = $this->roleInBusiness($business->id, $data['role_id']);
        $branchId = $data['branch_id'] ?? null;
        $this->assertBranch($business->id, $branchId);

        $user->roles()->syncWithoutDetaching([
            $role->id => [
                'business_id' => $business->id,
                'branch_id' => $branchId,
            ],
        ]);

        $user->load('businesses', 'roles.permissions');

        if ($recordAudit) {
            $this->audit->record('role.assigned', $user, after: [
                'user_id' => $user->id,
                'role_id' => $role->id,
                'business_id' => $business->id,
                'branch_id' => $branchId,
            ], request: $request);
        }

        return $user;
    }

    private function roleInBusiness(int $businessId, int $roleId): Role
    {
        $role = Role::query()
            ->whereKey($roleId)
            ->where(function ($query) use ($businessId): void {
                $query->whereNull('business_id')->orWhere('business_id', $businessId);
            })
            ->first();

        if (! $role) {
            throw ValidationException::withMessages(['role_id' => ['The selected role is outside the active business.']]);
        }

        return $role;
    }

    private function assertBranch(int $businessId, ?int $branchId): void
    {
        if ($branchId === null) {
            return;
        }

        $exists = Branch::query()
            ->where('business_id', $businessId)
            ->whereKey($branchId)
            ->exists();

        if (! $exists) {
            throw ValidationException::withMessages(['branch_id' => ['The selected branch is outside the active business.']]);
        }
    }
}
