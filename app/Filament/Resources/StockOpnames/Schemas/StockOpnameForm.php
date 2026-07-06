<?php

namespace App\Filament\Resources\StockOpnames\Schemas;

use App\Filament\Support\BranchOptions;
use App\Filament\Support\TenantContext;
use App\Models\Warehouse;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class StockOpnameForm
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
                    ->required()
                    ->numeric(),
                TextInput::make('opname_number')
                    ->required(),
                Select::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'counted' => 'Counted',
                        'posted' => 'Posted',
                        'void' => 'Void',
                    ])
                    ->required()
                    ->default('draft'),
                DateTimePicker::make('counted_at'),
                TextInput::make('counted_by')
                    ->numeric(),
            ]);
    }
}
