<?php

namespace App\Filament\Resources\SupplierPayments\Schemas;

use App\Filament\Support\TenantContext;
use App\Models\Account;
use App\Models\Supplier;
use App\Models\SupplierPayable;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class SupplierPaymentForm
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
                Select::make('supplier_id')
                    ->label('Supplier')
                    ->options(fn () => Supplier::query()
                        ->where('business_id', TenantContext::businessId())
                        ->orderBy('name')
                        ->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('supplier_payable_id')
                    ->label('Payable')
                    ->options(fn () => SupplierPayable::query()
                        ->where('business_id', TenantContext::businessId())
                        ->when(TenantContext::branchId(), fn ($query, $branchId) => $query->where('branch_id', $branchId))
                        ->orderByDesc('due_date')
                        ->pluck('payable_number', 'id'))
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('cash_account_id')
                    ->label('Cash Account')
                    ->options(fn () => Account::query()
                        ->where('business_id', TenantContext::businessId())
                        ->orderBy('name')
                        ->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('payment_number')
                    ->required(),
                DatePicker::make('payment_date')
                    ->required(),
                TextInput::make('amount')
                    ->required()
                    ->numeric()
                    ->prefix('Rp'),
                Select::make('payment_method')
                    ->options([
                        'cash' => 'Cash',
                        'bank_transfer' => 'Bank Transfer',
                        'card' => 'Card',
                        'ewallet' => 'E-Wallet',
                    ])
                    ->required()
                    ->default('cash'),
                TextInput::make('reference_number'),
                DateTimePicker::make('posted_at'),
                TextInput::make('posted_by')
                    ->numeric(),
                Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }
}
