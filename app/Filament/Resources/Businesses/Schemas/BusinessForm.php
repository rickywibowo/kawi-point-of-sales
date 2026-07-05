<?php

namespace App\Filament\Resources\Businesses\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class BusinessForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('legal_name'),
                TextInput::make('tax_number'),
                TextInput::make('currency')
                    ->required()
                    ->default('IDR'),
                TextInput::make('timezone')
                    ->required()
                    ->default('Asia/Makassar'),
                Toggle::make('is_active')
                    ->default(true)
                    ->required(),
            ]);
    }
}
