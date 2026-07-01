<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockBalance extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'business_id',
        'branch_id',
        'warehouse_id',
        'product_id',
        'quantity_on_hand',
        'average_cost',
        'stock_value',
    ];

    protected function casts(): array
    {
        return [
            'quantity_on_hand' => 'decimal:6',
            'average_cost' => 'decimal:2',
            'stock_value' => 'decimal:2',
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
