<?php

namespace App\Filament\Resources\OutletAccountMappings\Pages;

use App\Filament\Resources\OutletAccountMappings\OutletAccountMappingResource;
use App\Filament\Support\TenantContext;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateOutletAccountMapping extends CreateRecord
{
    protected static string $resource = OutletAccountMappingResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['business_id'] = TenantContext::businessId();
        $data['outlet_id'] = TenantContext::branchId();
        $this->ensureSingleActiveMapping($data);

        return $data;
    }

    private function ensureSingleActiveMapping(array $data): void
    {
        if (! ($data['is_active'] ?? false)) {
            return;
        }

        $exists = $this->getModel()::query()
            ->where('business_id', $data['business_id'])
            ->where('outlet_id', $data['outlet_id'])
            ->where('account_purpose', $data['account_purpose'])
            ->where('is_active', true)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'data.account_purpose' => 'Active mapping for this purpose already exists in the active outlet.',
            ]);
        }
    }
}
