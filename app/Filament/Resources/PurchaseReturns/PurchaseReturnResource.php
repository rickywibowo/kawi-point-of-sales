<?php

namespace App\Filament\Resources\PurchaseReturns;

use App\Filament\Resources\PurchaseReturns\Pages\CreatePurchaseReturn;
use App\Filament\Resources\PurchaseReturns\Pages\EditPurchaseReturn;
use App\Filament\Resources\PurchaseReturns\Pages\ListPurchaseReturns;
use App\Filament\Resources\PurchaseReturns\Schemas\PurchaseReturnForm;
use App\Filament\Resources\PurchaseReturns\Tables\PurchaseReturnsTable;
use App\Filament\Support\ScopesToBranch;
use App\Models\PurchaseReturn;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PurchaseReturnResource extends Resource
{
    use ScopesToBranch;

    protected static ?string $model = PurchaseReturn::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Purchase Returns';

    protected static string|UnitEnum|null $navigationGroup = 'Purchasing';

    public static function form(Schema $schema): Schema
    {
        return PurchaseReturnForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PurchaseReturnsTable::configure($table);
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
            'index' => ListPurchaseReturns::route('/'),
            'create' => CreatePurchaseReturn::route('/create'),
            'edit' => EditPurchaseReturn::route('/{record}/edit'),
        ];
    }
}
