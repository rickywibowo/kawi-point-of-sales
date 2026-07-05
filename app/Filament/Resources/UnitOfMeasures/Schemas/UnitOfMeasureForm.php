<?php

namespace App\Filament\Resources\UnitOfMeasures\Schemas;

use App\Filament\Support\TenantContext;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class UnitOfMeasureForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('business_id')
                    ->default(fn () => TenantContext::businessId())
                    ->required(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('code')
                    ->required(),
                Select::make('type')
                    ->options([
                        'unit' => 'Unit',
                        'weight' => 'Weight',
                        'volume' => 'Volume',
                        'length' => 'Length',
                    ])
                    ->required()
                    ->default('unit'),
                TextInput::make('base_multiplier')
                    ->required()
                    ->numeric()
                    ->default(1),
                Toggle::make('is_active')
                    ->default(true)
                    ->required(),
            ]);
    }
}
