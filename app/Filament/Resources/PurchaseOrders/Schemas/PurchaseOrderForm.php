<?php

namespace App\Filament\Resources\PurchaseOrders\Schemas;

use App\Filament\Support\TenantContext;
use App\Models\Supplier;
use App\Models\Warehouse;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class PurchaseOrderForm
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
                Select::make('warehouse_id')
                    ->label('Warehouse')
                    ->options(fn () => Warehouse::query()
                        ->where('business_id', TenantContext::businessId())
                        ->when(TenantContext::branchId(), fn ($query, $branchId) => $query->where('branch_id', $branchId))
                        ->orderBy('name')
                        ->pluck('name', 'id'))
                    ->searchable()
                    ->preload(),
                TextInput::make('po_number')
                    ->required(),
                Select::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'approved' => 'Approved',
                        'received' => 'Received',
                        'cancelled' => 'Cancelled',
                    ])
                    ->required()
                    ->default('draft'),
                DatePicker::make('order_date')
                    ->required(),
                DatePicker::make('expected_date'),
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
                TextInput::make('approved_by')
                    ->numeric(),
                DateTimePicker::make('approved_at'),
                Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }
}
