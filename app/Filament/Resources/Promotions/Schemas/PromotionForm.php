<?php

namespace App\Filament\Resources\Promotions\Schemas;

use App\Filament\Support\TenantContext;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PromotionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('business_id')
                    ->default(fn () => TenantContext::businessId())
                    ->required(),
                TextInput::make('code')
                    ->required()
                    ->maxLength(80),
                TextInput::make('name')
                    ->required()
                    ->maxLength(160),
                Select::make('type')
                    ->options([
                        'percent' => 'Percent',
                        'fixed' => 'Fixed',
                    ])
                    ->required()
                    ->default('percent'),
                TextInput::make('value')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('minimum_subtotal')
                    ->numeric()
                    ->default(0),
                TextInput::make('maximum_discount')
                    ->numeric(),
                TextInput::make('usage_limit')
                    ->numeric(),
                DatePicker::make('starts_on'),
                DatePicker::make('ends_on'),
                Toggle::make('is_active')
                    ->default(true)
                    ->required(),
            ]);
    }
}
