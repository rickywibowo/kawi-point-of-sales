<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItem extends Model
{
    protected $fillable = [
        'purchase_order_id',
        'product_id',
        'unit_of_measure_id',
        'quantity_ordered',
        'quantity_received',
        'unit_cost',
        'tax_rate',
        'tax_total',
        'line_total',
    ];

    protected function casts(): array
    {
        return [
            'quantity_ordered' => 'decimal:6',
            'quantity_received' => 'decimal:6',
            'unit_cost' => 'decimal:2',
            'tax_rate' => 'decimal:4',
            'tax_total' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
