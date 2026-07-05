<?php

namespace App\Filament\Resources\KitchenStations;

use App\Filament\Resources\KitchenStations\Pages\CreateKitchenStation;
use App\Filament\Resources\KitchenStations\Pages\EditKitchenStation;
use App\Filament\Resources\KitchenStations\Pages\ListKitchenStations;
use App\Filament\Resources\KitchenStations\Schemas\KitchenStationForm;
use App\Filament\Resources\KitchenStations\Tables\KitchenStationsTable;
use App\Filament\Support\ScopesToBranch;
use App\Models\KitchenStation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class KitchenStationResource extends Resource
{
    use ScopesToBranch;

    protected static ?string $model = KitchenStation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'POS Setup';

    public static function form(Schema $schema): Schema
    {
        return KitchenStationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return KitchenStationsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListKitchenStations::route('/'),
            'create' => CreateKitchenStation::route('/create'),
            'edit' => EditKitchenStation::route('/{record}/edit'),
        ];
    }
}
