<?php

namespace App\Filament\Resources\StockAdjustments\Schemas;

use App\Filament\Support\BranchOptions;
use App\Filament\Support\TenantContext;
use App\Models\Warehouse;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class StockAdjustmentForm
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
                TextInput::make('adjustment_number')
                    ->required(),
                Select::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'posted' => 'Posted',
                        'void' => 'Void',
                    ])
                    ->required()
                    ->default('posted'),
                Textarea::make('notes')
                    ->columnSpanFull(),
                TextInput::make('posted_by')
                    ->numeric(),
                DateTimePicker::make('posted_at'),
            ]);
    }
}
