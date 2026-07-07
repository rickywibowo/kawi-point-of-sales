<?php

namespace App\Filament\Resources\OutletAccountMappings\Pages;

use App\Filament\Resources\OutletAccountMappings\OutletAccountMappingResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditOutletAccountMapping extends EditRecord
{
    protected static string $resource = OutletAccountMappingResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['business_id'], $data['outlet_id']);
        $this->ensureSingleActiveMapping($data);

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    private function ensureSingleActiveMapping(array $data): void
    {
        if (! ($data['is_active'] ?? false)) {
            return;
        }

        $exists = $this->getModel()::query()
            ->whereKeyNot($this->record->id)
            ->where('business_id', $this->record->business_id)
            ->where('outlet_id', $this->record->outlet_id)
            ->where('account_purpose', $data['account_purpose'])
            ->where('is_active', true)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'data.account_purpose' => 'Active mapping for this purpose already exists in this outlet.',
            ]);
        }
    }
}
