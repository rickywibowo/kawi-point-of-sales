<?php

namespace App\Filament\Resources\StockBalances;

use App\Filament\Resources\StockBalances\Pages\CreateStockBalance;
use App\Filament\Resources\StockBalances\Pages\EditStockBalance;
use App\Filament\Resources\StockBalances\Pages\ListStockBalances;
use App\Filament\Resources\StockBalances\Schemas\StockBalanceForm;
use App\Filament\Resources\StockBalances\Tables\StockBalancesTable;
use App\Filament\Support\ScopesToBranch;
use App\Models\StockBalance;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class StockBalanceResource extends Resource
{
    use ScopesToBranch;

    protected static ?string $model = StockBalance::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Stock Balances';

    protected static string|UnitEnum|null $navigationGroup = 'Inventory';

    public static function form(Schema $schema): Schema
    {
        return StockBalanceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StockBalancesTable::configure($table);
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
            'index' => ListStockBalances::route('/'),
            'create' => CreateStockBalance::route('/create'),
            'edit' => EditStockBalance::route('/{record}/edit'),
        ];
    }
}
