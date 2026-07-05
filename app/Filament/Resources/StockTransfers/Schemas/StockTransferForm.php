<?php

namespace App\Filament\Resources\StockTransfers\Schemas;

use App\Filament\Support\TenantContext;
use App\Models\Branch;
use App\Models\Warehouse;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class StockTransferForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('business_id')
                    ->default(fn () => TenantContext::businessId())
                    ->required(),
                Select::make('from_branch_id')
                    ->label('From Branch')
                    ->options(fn () => Branch::query()
                        ->where('business_id', TenantContext::businessId())
                        ->orderBy('name')
                        ->pluck('name', 'id'))
                    ->searchable()
                    ->preload(),
                Select::make('to_branch_id')
                    ->label('To Branch')
                    ->options(fn () => Branch::query()
                        ->where('business_id', TenantContext::businessId())
                        ->orderBy('name')
                        ->pluck('name', 'id'))
                    ->searchable()
                    ->preload(),
                Select::make('from_warehouse_id')
                    ->label('From Warehouse')
                    ->options(fn () => Warehouse::query()
                        ->where('business_id', TenantContext::businessId())
                        ->orderBy('name')
                        ->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->required()
                    ->numeric(),
                Select::make('to_warehouse_id')
                    ->label('To Warehouse')
                    ->options(fn () => Warehouse::query()
                        ->where('business_id', TenantContext::businessId())
                        ->orderBy('name')
                        ->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->required()
                    ->numeric(),
                TextInput::make('transfer_number')
                    ->required(),
                Select::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'sent' => 'Sent',
                        'received' => 'Received',
                        'void' => 'Void',
                    ])
                    ->required()
                    ->default('draft'),
                Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }
}
