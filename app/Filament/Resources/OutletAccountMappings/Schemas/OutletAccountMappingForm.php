<?php

namespace App\Filament\Resources\OutletAccountMappings\Schemas;

use App\Filament\Support\TenantContext;
use App\Models\Account;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class OutletAccountMappingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('business_id')
                    ->default(fn () => TenantContext::businessId())
                    ->required(),
                Hidden::make('outlet_id')
                    ->default(fn () => TenantContext::branchId())
                    ->required(),
                Select::make('account_purpose')
                    ->options(self::purposeOptions())
                    ->required()
                    ->searchable(),
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
                Toggle::make('is_active')
                    ->default(true)
                    ->required(),
            ]);
    }

    public static function purposeOptions(): array
    {
        return [
            'cash' => 'Cash',
            'qris_receivable' => 'QRIS Receivable',
            'bank' => 'Bank',
            'cash_shortage' => 'Cash Shortage',
            'cash_overage' => 'Cash Overage',
        ];
    }
}
