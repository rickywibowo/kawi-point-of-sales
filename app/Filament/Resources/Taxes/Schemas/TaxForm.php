<?php

namespace App\Filament\Resources\Taxes\Schemas;

use App\Filament\Support\TenantContext;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TaxForm
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
                TextInput::make('rate')
                    ->required()
                    ->numeric()
                    ->default(0),
                Toggle::make('is_inclusive')
                    ->default(false)
                    ->required(),
                Toggle::make('is_active')
                    ->default(true)
                    ->required(),
            ]);
    }
}
