<?php

namespace App\Filament\Support;

use Illuminate\Database\Eloquent\Builder;

trait ScopesToBusiness
{
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $businessId = TenantContext::businessId();

        return $businessId ? $query->where('business_id', $businessId) : $query;
    }
}
