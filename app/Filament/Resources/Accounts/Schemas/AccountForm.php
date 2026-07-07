<?php

namespace App\Filament\Resources\Accounts\Schemas;

use App\Filament\Support\TenantContext;
use App\Models\Account;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;

class AccountForm
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
                    ->maxLength(50)
                    ->rule(fn (?Account $record) => Rule::unique('accounts', 'code')
                        ->where('business_id', TenantContext::businessId())
                        ->ignore($record?->id)),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Select::make('type')
                    ->options(self::typeOptions())
                    ->required()
                    ->searchable(),
                Select::make('normal_balance')
                    ->options([
                        'debit' => 'Debit',
                        'credit' => 'Credit',
                    ])
                    ->required(),
                Select::make('parent_id')
                    ->label('Parent account')
                    ->relationship(
                        'parent',
                        'name',
                        fn (Builder $query, ?Account $record) => $query
                            ->where('business_id', TenantContext::businessId())
                            ->when($record?->id, fn (Builder $query, int $id) => $query->whereKeyNot($id))
                            ->orderBy('code'),
                    )
                    ->getOptionLabelFromRecordUsing(fn (Account $record) => "{$record->code} - {$record->name}")
                    ->searchable(['code', 'name'])
                    ->preload(),
                Toggle::make('is_cash_account')
                    ->label('Cash account'),
                Toggle::make('is_system')
                    ->label('System account'),
                Toggle::make('is_active')
                    ->default(true)
                    ->required(),
            ]);
    }

    public static function typeOptions(): array
    {
        return [
            'asset' => 'Asset',
            'liability' => 'Liability',
            'equity' => 'Equity',
            'income' => 'Income',
            'cogs' => 'COGS',
            'expense' => 'Expense',
            'other_income' => 'Other Income',
            'other_expense' => 'Other Expense',
        ];
    }
}
