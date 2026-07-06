<?php

namespace App\Filament\Resources\SupplierPayables\Schemas;

use App\Filament\Support\TenantContext;
use App\Models\GoodsReceipt;
use App\Models\Supplier;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SupplierPayableForm
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
                Select::make('goods_receipt_id')
                    ->label('Goods Receipt')
                    ->options(fn () => GoodsReceipt::query()
                        ->where('business_id', TenantContext::businessId())
                        ->when(TenantContext::branchId(), fn ($query, $branchId) => $query->where('branch_id', $branchId))
                        ->orderByDesc('received_date')
                        ->pluck('receipt_number', 'id'))
                    ->searchable()
                    ->preload(),
                TextInput::make('payable_number')
                    ->required(),
                TextInput::make('amount')
                    ->required()
                    ->numeric()
                    ->prefix('Rp'),
                TextInput::make('paid_amount')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->prefix('Rp'),
                Select::make('status')
                    ->options([
                        'open' => 'Open',
                        'partial' => 'Partial',
                        'paid' => 'Paid',
                        'void' => 'Void',
                    ])
                    ->required()
                    ->default('open'),
                DatePicker::make('due_date'),
            ]);
    }
}
