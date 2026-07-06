<?php

namespace App\Services\Tenancy;

use App\Models\Branch;
use App\Models\Business;
use App\Models\User;
use Symfony\Component\HttpKernel\Exception\HttpException;

class TenantContext
{
    public function resolve(User $user, ?string $businessUuid, ?string $branchUuid): array
    {
        $business = $businessUuid
            ? Business::query()->where('uuid', $businessUuid)->where('is_active', true)->first()
            : $user->currentBusiness;

        if (! $business || ! $user->belongsToBusiness($business->id)) {
            throw new HttpException(403, 'Business context is not accessible.');
        }

        $branch = null;

        if ($branchUuid) {
            $branch = Branch::query()
                ->where('business_id', $business->id)
                ->where('uuid', $branchUuid)
                ->where('is_active', true)
                ->first();

            if (! $branch) {
                throw new HttpException(403, 'Branch context is not accessible.');
            }
        } elseif ($user->currentBranch?->business_id === $business->id) {
            $branch = $user->currentBranch;
        }

        if (! $user->canAccessBranchContext($business->id, $branch?->id)) {
            throw new HttpException(403, 'Branch context is not accessible.');
        }

        return [$business, $branch];
    }
}
