<?php

namespace App\Filament\Resources\JournalEntries\Schemas;

use App\Filament\Support\TenantContext;
use App\Models\Account;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class JournalEntryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('business_id')
                    ->default(fn () => TenantContext::businessId())
                    ->required(),
                Hidden::make('outlet_id')
                    ->default(fn () => TenantContext::branchId()),
                Hidden::make('branch_id')
                    ->default(fn () => TenantContext::branchId()),
                Hidden::make('status')
                    ->default('draft'),
                DatePicker::make('entry_date')
                    ->label('Entry date')
                    ->default(now())
                    ->required(),
                TextInput::make('reference_no')
                    ->label('Reference no')
                    ->maxLength(80),
                Textarea::make('description')
                    ->maxLength(1000)
                    ->columnSpanFull(),
                TextInput::make('status_display')
                    ->label('Status')
                    ->disabled()
                    ->dehydrated(false)
                    ->formatStateUsing(fn ($record) => $record?->status ?? 'draft'),
                Repeater::make('lines')
                    ->relationship()
                    ->schema([
                        Select::make('account_id')
                            ->label('Account')
                            ->relationship(
                                'account',
                                'name',
                                fn (Builder $query) => $query
                                    ->where('business_id', TenantContext::businessId())
                                    ->where('is_active', true)
                                    ->orderBy('code'),
                            )
                            ->getOptionLabelFromRecordUsing(fn (Account $record) => "{$record->code} - {$record->name}")
                            ->searchable(['code', 'name'])
                            ->preload()
                            ->required(),
                        TextInput::make('description')
                            ->maxLength(255),
                        TextInput::make('debit')
                            ->numeric()
                            ->minValue(0)
                            ->default(0),
                        TextInput::make('credit')
                            ->numeric()
                            ->minValue(0)
                            ->default(0),
                    ])
                    ->columns(4)
                    ->minItems(2)
                    ->defaultItems(2)
                    ->columnSpanFull(),
            ]);
    }
}
