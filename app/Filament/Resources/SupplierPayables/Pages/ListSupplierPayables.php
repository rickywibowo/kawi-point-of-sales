<?php

namespace App\Filament\Resources\SupplierPayables\Pages;

use App\Filament\Resources\SupplierPayables\SupplierPayableResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSupplierPayables extends ListRecords
{
    protected static string $resource = SupplierPayableResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
