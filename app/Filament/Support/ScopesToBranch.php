<?php

namespace App\Filament\Support;

use Illuminate\Database\Eloquent\Builder;

trait ScopesToBranch
{
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $businessId = TenantContext::businessId();
        $branchId = TenantContext::branchId();

        return $query
            ->when($businessId, fn (Builder $query) => $query->where('business_id', $businessId))
            ->when($branchId, fn (Builder $query) => $query->where('branch_id', $branchId));
    }
}
