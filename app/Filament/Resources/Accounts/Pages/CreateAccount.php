<?php

namespace App\Filament\Resources\Accounts\Pages;

use App\Filament\Resources\Accounts\AccountResource;
use App\Filament\Support\TenantContext;
use Filament\Resources\Pages\CreateRecord;

class CreateAccount extends CreateRecord
{
    protected static string $resource = AccountResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['business_id'] = TenantContext::businessId();
        $data['is_cash'] = $data['is_cash_account'] ?? false;

        return $data;
    }
}
