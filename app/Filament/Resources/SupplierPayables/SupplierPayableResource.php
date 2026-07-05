<?php

namespace App\Filament\Resources\SupplierPayables;

use App\Filament\Resources\SupplierPayables\Pages\CreateSupplierPayable;
use App\Filament\Resources\SupplierPayables\Pages\EditSupplierPayable;
use App\Filament\Resources\SupplierPayables\Pages\ListSupplierPayables;
use App\Filament\Resources\SupplierPayables\Schemas\SupplierPayableForm;
use App\Filament\Resources\SupplierPayables\Tables\SupplierPayablesTable;
use App\Filament\Support\ScopesToBranch;
use App\Models\SupplierPayable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SupplierPayableResource extends Resource
{
    use ScopesToBranch;

    protected static ?string $model = SupplierPayable::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Supplier Payables';

    protected static string|UnitEnum|null $navigationGroup = 'Purchasing';

    public static function form(Schema $schema): Schema
    {
        return SupplierPayableForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SupplierPayablesTable::configure($table);
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
            'index' => ListSupplierPayables::route('/'),
            'create' => CreateSupplierPayable::route('/create'),
            'edit' => EditSupplierPayable::route('/{record}/edit'),
        ];
    }
}
