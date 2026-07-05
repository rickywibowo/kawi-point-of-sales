<?php

namespace App\Filament\Resources\GoodsReceipts\Schemas;

use App\Filament\Support\TenantContext;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Warehouse;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class GoodsReceiptForm
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
                Select::make('purchase_order_id')
                    ->label('Purchase Order')
                    ->options(fn () => PurchaseOrder::query()
                        ->where('business_id', TenantContext::businessId())
                        ->when(TenantContext::branchId(), fn ($query, $branchId) => $query->where('branch_id', $branchId))
                        ->orderByDesc('order_date')
                        ->pluck('po_number', 'id'))
                    ->searchable()
                    ->preload(),
                Select::make('supplier_id')
                    ->label('Supplier')
                    ->options(fn () => Supplier::query()
                        ->where('business_id', TenantContext::businessId())
                        ->orderBy('name')
                        ->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('warehouse_id')
                    ->label('Warehouse')
                    ->options(fn () => Warehouse::query()
                        ->where('business_id', TenantContext::businessId())
                        ->when(TenantContext::branchId(), fn ($query, $branchId) => $query->where('branch_id', $branchId))
                        ->orderBy('name')
                        ->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('receipt_number')
                    ->required(),
                Select::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'posted' => 'Posted',
                        'void' => 'Void',
                    ])
                    ->required()
                    ->default('posted'),
                DatePicker::make('received_date')
                    ->required(),
                TextInput::make('subtotal')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->prefix('Rp'),
                TextInput::make('tax_total')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->prefix('Rp'),
                TextInput::make('grand_total')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->prefix('Rp'),
                TextInput::make('received_by')
                    ->numeric(),
                DateTimePicker::make('posted_at'),
                Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }
}
