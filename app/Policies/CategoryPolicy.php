<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    public function view(User $user, Category $category): bool
    {
        return $user->belongsToBusiness($category->business_id)
            && $user->canInTenant('inventory.view', $category->business_id);
    }

    public function manage(User $user, Category $category): bool
    {
        return $user->belongsToBusiness($category->business_id)
            && $user->canInTenant('inventory.adjust', $category->business_id);
    }
}
