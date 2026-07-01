<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockLedger extends Model
{
    use BelongsToTenant, UsesUuid;

    protected $fillable = [
        'business_id',
        'branch_id',
        'warehouse_id',
        'product_id',
        'unit_of_measure_id',
        'uuid',
        'movement_type',
        'quantity_in',
        'quantity_out',
        'unit_cost',
        'total_cost',
        'source_type',
        'source_id',
        'reference_number',
        'notes',
        'occurred_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity_in' => 'decimal:6',
            'quantity_out' => 'decimal:6',
            'unit_cost' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'occurred_at' => 'datetime',
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
