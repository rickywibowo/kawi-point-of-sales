<?php

namespace App\Filament\Support;

use App\Models\Branch;

class BranchOptions
{
    public static function forCurrentBusiness(): array
    {
        return Branch::query()
            ->where('business_id', TenantContext::businessId())
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }
}
