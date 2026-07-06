<?php

namespace App\Filament\Resources\PurchaseReturns\Schemas;

use App\Filament\Support\BranchOptions;
use App\Filament\Support\TenantContext;
use App\Models\GoodsReceipt;
use App\Models\Supplier;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class PurchaseReturnForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('business_id')
                    ->default(fn () => TenantContext::businessId())
                    ->required(),
                Select::make('branch_id')
                    ->label('Branch')
                    ->options(fn () => BranchOptions::forCurrentBusiness())
                    ->default(fn () => TenantContext::branchId())
                    ->searchable()
                    ->preload()
                    ->live()
                    ->required(),
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
                    ->options(fn ($get) => GoodsReceipt::query()
                        ->where('business_id', TenantContext::businessId())
                        ->when($get('branch_id'), fn ($query, $branchId) => $query->where('branch_id', $branchId))
                        ->orderByDesc('received_date')
                        ->pluck('receipt_number', 'id'))
                    ->searchable()
                    ->preload(),
                TextInput::make('return_number')
                    ->required(),
                Select::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'posted' => 'Posted',
                        'void' => 'Void',
                    ])
                    ->required()
                    ->default('draft'),
                DatePicker::make('return_date')
                    ->required(),
                TextInput::make('grand_total')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->prefix('Rp'),
                Textarea::make('reason')
                    ->columnSpanFull(),
            ]);
    }
}
