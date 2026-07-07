<?php

namespace App\Support;

use App\Models\Branch;
use App\Models\Business;
use App\Models\User;

class UserContextOptions
{
    public static function forUser(User $user): array
    {
        $businesses = $user->businesses()
            ->where('is_active', true)
            ->with(['branches' => fn ($query) => $query
                ->where('is_active', true)
                ->whereHas('users', fn ($query) => $query->whereKey($user->id))
                ->orderBy('name')])
            ->orderBy('name')
            ->get();

        return $businesses->map(function (Business $business): array {
            $branches = $business->branches
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
                'can_select_all_branches' => false,
                'branches' => $branches,
            ];
        })->filter(fn (array $business) => count($business['branches']) > 0)->values()->all();
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
