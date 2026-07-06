<?php

namespace App\Filament\Resources\KitchenStations\Schemas;

use App\Filament\Support\BranchOptions;
use App\Filament\Support\TenantContext;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class KitchenStationForm
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
                    ->required(),
                TextInput::make('code')
                    ->required()
                    ->maxLength(40),
                TextInput::make('name')
                    ->required()
                    ->maxLength(120),
                TextInput::make('sort_order')
                    ->required()
                    ->numeric()
                    ->default(10),
                Toggle::make('is_active')
                    ->default(true)
                    ->required(),
            ]);
    }
}
