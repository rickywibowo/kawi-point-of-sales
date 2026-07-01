<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockAdjustmentItem extends Model
{
    protected $fillable = [
        'stock_adjustment_id',
        'product_id',
        'unit_of_measure_id',
        'quantity_delta',
        'unit_cost',
        'notes',
    ];

    protected function casts(): array
    {
        return ['quantity_delta' => 'decimal:6', 'unit_cost' => 'decimal:2'];
    }

    public function stockAdjustment(): BelongsTo
    {
        return $this->belongsTo(StockAdjustment::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
