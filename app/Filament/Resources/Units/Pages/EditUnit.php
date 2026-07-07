<?php

namespace App\Filament\Resources\Units\Pages;

use App\Filament\Resources\Units\UnitResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUnit extends EditRecord
{
    protected static string $resource = UnitResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['business_id']);

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
