<?php

namespace App\Filament\Resources\DiningTables\Schemas;

use App\Filament\Support\TenantContext;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class DiningTableForm
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
                TextInput::make('code')
                    ->required()
                    ->maxLength(40),
                TextInput::make('name')
                    ->required()
                    ->maxLength(120),
                TextInput::make('capacity')
                    ->required()
                    ->numeric()
                    ->default(2),
                TextInput::make('section')
                    ->required()
                    ->default('Main'),
                Select::make('status')
                    ->options([
                        'available' => 'Available',
                        'occupied' => 'Occupied',
                        'reserved' => 'Reserved',
                        'cleaning' => 'Cleaning',
                    ])
                    ->required()
                    ->default('available'),
            ]);
    }
}
