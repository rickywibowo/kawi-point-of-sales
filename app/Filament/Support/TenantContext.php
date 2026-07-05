<?php

namespace App\Filament\Support;

use App\Models\Business;

class TenantContext
{
    public static function businessId(): ?int
    {
        return auth()->user()?->current_business_id
            ?? auth()->user()?->businesses()->value('businesses.id')
            ?? Business::query()->value('id');
    }
}
