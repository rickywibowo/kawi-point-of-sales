<?php

namespace App\Filament\Resources\ProductionOrders\Schemas;

use App\Filament\Support\BranchOptions;
use App\Filament\Support\TenantContext;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\Warehouse;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class ProductionOrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('business_id')
                    ->default(fn () => TenantContext::businessId())
                    ->required(),
                Select::make('branch_id')
                    ->label('Branch')
                    ->options(fn () => BranchOptions::forCurrentBusiness())
                    ->default(fn () => TenantContext::branchId())
                    ->searchable()
                    ->preload()
                    ->live()
                    ->required(),
                Select::make('warehouse_id')
                    ->label('Warehouse')
                    ->options(fn ($get) => Warehouse::query()
                        ->where('business_id', TenantContext::businessId())
                        ->when($get('branch_id'), fn ($query, $branchId) => $query->where('branch_id', $branchId))
                        ->orderBy('name')
                        ->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('recipe_id')
                    ->label('Recipe')
                    ->options(fn ($get) => Recipe::query()
                        ->where('business_id', TenantContext::businessId())
                        ->when($get('branch_id'), fn ($query, $branchId) => $query->whereHas('product', fn ($query) => $query->where('branch_id', $branchId)))
                        ->orderBy('name')
                        ->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('product_id')
                    ->label('Output Product')
                    ->options(fn ($get) => Product::query()
                        ->where('business_id', TenantContext::businessId())
                        ->when($get('branch_id'), fn ($query, $branchId) => $query->where('branch_id', $branchId))
                        ->orderBy('name')
                        ->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('production_number')
                    ->required(),
                Select::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'posted' => 'Posted',
                        'void' => 'Void',
                    ])
                    ->required()
                    ->default('posted'),
                TextInput::make('planned_quantity')
                    ->required()
                    ->numeric(),
                TextInput::make('actual_quantity')
                    ->required()
                    ->numeric(),
                TextInput::make('waste_quantity')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('total_cost')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->prefix('Rp'),
                DateTimePicker::make('produced_at')
                    ->required(),
                TextInput::make('produced_by')
                    ->numeric(),
                Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }
}
