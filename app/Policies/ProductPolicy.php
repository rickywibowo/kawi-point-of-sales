<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function view(User $user, Product $product): bool
    {
        return $user->belongsToBusiness($product->business_id)
            && $user->canInTenant('inventory.view', $product->business_id);
    }

    public function manage(User $user, Product $product): bool
    {
        return $user->belongsToBusiness($product->business_id)
            && $user->canInTenant('inventory.adjust', $product->business_id);
    }
}
