<?php

namespace App\Filament\Resources\DiningTables;

use App\Filament\Resources\DiningTables\Pages\CreateDiningTable;
use App\Filament\Resources\DiningTables\Pages\EditDiningTable;
use App\Filament\Resources\DiningTables\Pages\ListDiningTables;
use App\Filament\Resources\DiningTables\Schemas\DiningTableForm;
use App\Filament\Resources\DiningTables\Tables\DiningTablesTable;
use App\Filament\Support\ScopesToBranch;
use App\Models\DiningTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class DiningTableResource extends Resource
{
    use ScopesToBranch;

    protected static ?string $model = DiningTable::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'POS Setup';

    public static function form(Schema $schema): Schema
    {
        return DiningTableForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DiningTablesTable::configure($table);
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
            'index' => ListDiningTables::route('/'),
            'create' => CreateDiningTable::route('/create'),
            'edit' => EditDiningTable::route('/{record}/edit'),
        ];
    }
}
