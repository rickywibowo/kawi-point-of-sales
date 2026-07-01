<?php

namespace App\Policies;

use App\Models\Business;
use App\Models\User;

class BusinessPolicy
{
    public function view(User $user, Business $business): bool
    {
        return $user->belongsToBusiness($business->id);
    }

    public function manageUsers(User $user, Business $business): bool
    {
        return $user->canInTenant('users.manage', $business->id);
    }
}
