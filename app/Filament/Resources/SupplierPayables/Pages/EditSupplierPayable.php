<?php

namespace App\Filament\Resources\SupplierPayables\Pages;

use App\Filament\Resources\SupplierPayables\SupplierPayableResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSupplierPayable extends EditRecord
{
    protected static string $resource = SupplierPayableResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
