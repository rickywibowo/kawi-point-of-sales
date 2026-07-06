<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Filament\Support\TenantContext;
use App\Models\Category;
use App\Models\KitchenStation;
use App\Models\Tax;
use App\Models\UnitOfMeasure;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ProductForm
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
                Select::make('category_id')
                    ->label('Category')
                    ->options(fn () => Category::query()
                        ->where('business_id', TenantContext::businessId())
                        ->when(TenantContext::branchId(), fn ($query, $branchId) => $query->where('branch_id', $branchId))
                        ->orderBy('name')
                        ->pluck('name', 'id'))
                    ->searchable()
                    ->preload(),
                Select::make('unit_of_measure_id')
                    ->label('Unit')
                    ->options(fn () => UnitOfMeasure::query()
                        ->where('business_id', TenantContext::businessId())
                        ->orderBy('name')
                        ->pluck('name', 'id'))
                    ->searchable()
                    ->preload(),
                Select::make('tax_id')
                    ->label('Tax')
                    ->options(fn () => Tax::query()
                        ->where('business_id', TenantContext::businessId())
                        ->orderBy('name')
                        ->pluck('name', 'id'))
                    ->searchable()
                    ->preload(),
                Select::make('kitchen_station_id')
                    ->label('Kitchen Station')
                    ->options(fn () => KitchenStation::query()
                        ->where('business_id', TenantContext::businessId())
                        ->when(TenantContext::branchId(), fn ($query, $branchId) => $query->where('branch_id', $branchId))
                        ->orderBy('name')
                        ->pluck('name', 'id'))
                    ->searchable()
                    ->preload(),
                TextInput::make('name')
                    ->required(),
                Select::make('type')
                    ->options([
                        'goods' => 'Goods',
                        'food' => 'Food',
                        'beverage' => 'Beverage',
                        'service' => 'Service',
                    ])
                    ->required()
                    ->default('goods'),
                TextInput::make('sku')
                    ->label('SKU'),
                TextInput::make('barcode'),
                TextInput::make('base_price')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->prefix('Rp'),
                TextInput::make('cost_price')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->prefix('Rp'),
                Toggle::make('track_stock')
                    ->default(true)
                    ->required(),
                Toggle::make('is_active')
                    ->default(true)
                    ->required(),
            ]);
    }
}
