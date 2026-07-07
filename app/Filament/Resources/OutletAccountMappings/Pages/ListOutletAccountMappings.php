<?php

namespace App\Filament\Resources\OutletAccountMappings\Pages;

use App\Filament\Resources\OutletAccountMappings\OutletAccountMappingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOutletAccountMappings extends ListRecords
{
    protected static string $resource = OutletAccountMappingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
