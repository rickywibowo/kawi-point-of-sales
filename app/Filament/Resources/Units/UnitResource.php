<?php

namespace App\Filament\Resources\Units;

use App\Filament\Resources\Units\Pages\CreateUnit;
use App\Filament\Resources\Units\Pages\EditUnit;
use App\Filament\Resources\Units\Pages\ListUnits;
use App\Filament\Resources\Units\Pages\ViewUnit;
use App\Filament\Resources\Units\Schemas\UnitForm;
use App\Filament\Resources\Units\Tables\UnitsTable;
use App\Filament\Support\ScopesToBusiness;
use App\Models\Unit;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class UnitResource extends Resource
{
    use ScopesToBusiness;

    protected static ?string $model = Unit::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedScale;

    protected static ?string $navigationLabel = 'Units';

    protected static ?string $modelLabel = 'Unit';

    protected static string|UnitEnum|null $navigationGroup = 'Master Data';

    public static function form(Schema $schema): Schema
    {
        return UnitForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UnitsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUnits::route('/'),
            'create' => CreateUnit::route('/create'),
            'view' => ViewUnit::route('/{record}'),
            'edit' => EditUnit::route('/{record}/edit'),
        ];
    }
}
