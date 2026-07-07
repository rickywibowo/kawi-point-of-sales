<?php

namespace App\Filament\Resources\OutletAccountMappings;

use App\Filament\Resources\OutletAccountMappings\Pages\CreateOutletAccountMapping;
use App\Filament\Resources\OutletAccountMappings\Pages\EditOutletAccountMapping;
use App\Filament\Resources\OutletAccountMappings\Pages\ListOutletAccountMappings;
use App\Filament\Resources\OutletAccountMappings\Schemas\OutletAccountMappingForm;
use App\Filament\Resources\OutletAccountMappings\Tables\OutletAccountMappingsTable;
use App\Filament\Support\ScopesToBusiness;
use App\Models\OutletAccountMapping;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class OutletAccountMappingResource extends Resource
{
    use ScopesToBusiness;

    protected static ?string $model = OutletAccountMapping::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static ?string $navigationLabel = 'Outlet Account Mappings';

    protected static string|UnitEnum|null $navigationGroup = 'Accounting';

    public static function form(Schema $schema): Schema
    {
        return OutletAccountMappingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OutletAccountMappingsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOutletAccountMappings::route('/'),
            'create' => CreateOutletAccountMapping::route('/create'),
            'edit' => EditOutletAccountMapping::route('/{record}/edit'),
        ];
    }
}
