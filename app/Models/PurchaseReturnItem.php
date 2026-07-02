<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseReturnItem extends Model
{
    protected $fillable = [
        'purchase_return_id',
        'goods_receipt_item_id',
        'product_id',
        'unit_of_measure_id',
        'quantity_returned',
        'unit_cost',
        'tax_rate',
        'tax_total',
        'line_total',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'quantity_returned' => 'decimal:6',
            'unit_cost' => 'decimal:2',
            'tax_rate' => 'decimal:4',
            'tax_total' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    public function purchaseReturn(): BelongsTo
    {
        return $this->belongsTo(PurchaseReturn::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
