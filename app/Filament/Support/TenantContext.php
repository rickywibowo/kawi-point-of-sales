<?php

namespace App\Filament\Support;

use App\Models\Business;
use App\Models\Branch;

class TenantContext
{
    public static function businessId(): ?int
    {
        return request()->session()->get('active_business_id')
            ?? auth()->user()?->current_business_id
            ?? auth()->user()?->businesses()->value('businesses.id')
            ?? Business::query()->value('id');
    }

    public static function branchId(): ?int
    {
        return request()->session()->get('active_outlet_id')
            ?? auth()->user()?->current_branch_id
            ?? Branch::query()
                ->where('business_id', static::businessId())
                ->value('id');
    }
}
