<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockOpnameItem extends Model
{
    protected $fillable = [
        'stock_opname_id',
        'product_id',
        'unit_of_measure_id',
        'system_quantity',
        'counted_quantity',
        'variance_quantity',
        'unit_cost',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'system_quantity' => 'decimal:6',
            'counted_quantity' => 'decimal:6',
            'variance_quantity' => 'decimal:6',
            'unit_cost' => 'decimal:2',
        ];
    }

    public function stockOpname(): BelongsTo
    {
        return $this->belongsTo(StockOpname::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
