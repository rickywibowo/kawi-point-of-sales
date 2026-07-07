<?php

namespace App\Filament\Resources\Units\Schemas;

use App\Filament\Support\TenantContext;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class UnitForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('business_id')
                    ->default(fn () => TenantContext::businessId())
                    ->required(),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('symbol')
                    ->maxLength(50),
                Select::make('type')
                    ->options(self::typeOptions())
                    ->searchable(),
                Toggle::make('is_active')
                    ->default(true)
                    ->required(),
            ]);
    }

    public static function typeOptions(): array
    {
        return [
            'quantity' => 'Quantity',
            'weight' => 'Weight',
            'volume' => 'Volume',
            'custom' => 'Custom',
        ];
    }
}
