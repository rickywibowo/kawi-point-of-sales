<?php

namespace App\Support;

use App\Models\Branch;
use App\Models\Business;
use App\Models\User;

class UserContextOptions
{
    public static function forUser(User $user): array
    {
        $businesses = $user->isPlatformSuperAdmin()
            ? Business::query()->where('is_active', true)->with('branches')->orderBy('name')->get()
            : $user->businesses()->where('is_active', true)->with('branches')->orderBy('name')->get();

        return $businesses->map(function (Business $business) use ($user): array {
            $allBranches = $user->isPlatformSuperAdmin()
                || $user->isBusinessOwner($business->id)
                || $user->hasBusinessLevelRole($business->id);

            $allowedBranchIds = $allBranches
                ? null
                : $user->roles()
                    ->where('model_has_roles.business_id', $business->id)
                    ->whereNotNull('model_has_roles.branch_id')
                    ->pluck('model_has_roles.branch_id')
                    ->unique()
                    ->values();

            $branches = $business->branches
                ->when($allowedBranchIds !== null, fn ($branches) => $branches->whereIn('id', $allowedBranchIds))
                ->values()
                ->map(fn (Branch $branch): array => [
                    'id' => $branch->id,
                    'uuid' => $branch->uuid,
                    'name' => $branch->name,
                    'code' => $branch->code,
                ]);

            return [
                'id' => $business->id,
                'uuid' => $business->uuid,
                'name' => $business->name,
                'can_select_all_branches' => $allBranches,
                'branches' => $branches,
            ];
        })->values()->all();
    }

    public static function selectOptions(User $user): array
    {
        return collect(static::forUser($user))
            ->flatMap(fn (array $business) => collect($business['branches'])
                ->mapWithKeys(fn (array $branch) => [
                    $business['id'].':'.$branch['id'] => $business['name'].' / '.$branch['name'].' ('.$branch['code'].')',
                ]))
            ->all();
    }
}
