<?php

namespace App\Filament\Resources\Recipes\Schemas;

use App\Filament\Support\TenantContext;
use App\Models\Product;
use App\Models\UnitOfMeasure;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class RecipeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('business_id')
                    ->default(fn () => TenantContext::businessId())
                    ->required(),
                Select::make('product_id')
                    ->label('Output Product')
                    ->options(fn () => Product::query()
                        ->where('business_id', TenantContext::businessId())
                        ->when(TenantContext::branchId(), fn ($query, $branchId) => $query->where('branch_id', $branchId))
                        ->orderBy('name')
                        ->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('yield_quantity')
                    ->required()
                    ->numeric()
                    ->default(1),
                Select::make('yield_unit_id')
                    ->label('Yield Unit')
                    ->options(fn () => UnitOfMeasure::query()
                        ->where('business_id', TenantContext::businessId())
                        ->orderBy('name')
                        ->pluck('name', 'id'))
                    ->searchable()
                    ->preload(),
                TextInput::make('waste_percentage')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('computed_cost')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->prefix('$'),
                TextInput::make('version')
                    ->required()
                    ->numeric()
                    ->default(1),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
