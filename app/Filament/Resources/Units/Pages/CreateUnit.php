<?php

namespace App\Filament\Resources\Units\Pages;

use App\Filament\Resources\Units\UnitResource;
use App\Filament\Support\TenantContext;
use Filament\Resources\Pages\CreateRecord;

class CreateUnit extends CreateRecord
{
    protected static string $resource = UnitResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['business_id'] = TenantContext::businessId();

        return $data;
    }
}
