<?php

namespace App\Filament\Resources\StockBalances\Schemas;

use App\Filament\Support\TenantContext;
use App\Models\Product;
use App\Models\Warehouse;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class StockBalanceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('business_id')
                    ->default(fn () => TenantContext::businessId())
                    ->required(),
                Hidden::make('branch_id')
                    ->default(fn () => TenantContext::branchId()),
                Select::make('warehouse_id')
                    ->label('Warehouse')
                    ->options(fn () => Warehouse::query()
                        ->where('business_id', TenantContext::businessId())
                        ->when(TenantContext::branchId(), fn ($query, $branchId) => $query->where('branch_id', $branchId))
                        ->orderBy('name')
                        ->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('product_id')
                    ->label('Product')
                    ->options(fn () => Product::query()
                        ->where('business_id', TenantContext::businessId())
                        ->orderBy('name')
                        ->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('quantity_on_hand')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('average_cost')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->prefix('$'),
                TextInput::make('stock_value')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}
