<?php

namespace App\Policies;

use App\Models\Branch;
use App\Models\User;

class BranchPolicy
{
    public function view(User $user, Branch $branch): bool
    {
        return $user->belongsToBusiness($branch->business_id);
    }

    public function manageInventory(User $user, Branch $branch): bool
    {
        return $user->canInTenant('inventory.adjust', $branch->business_id, $branch->id);
    }
}
