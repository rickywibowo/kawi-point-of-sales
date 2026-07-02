<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductionOrder extends Model
{
    use BelongsToTenant, UsesUuid;

    protected $fillable = [
        'business_id',
        'branch_id',
        'warehouse_id',
        'recipe_id',
        'product_id',
        'uuid',
        'production_number',
        'status',
        'planned_quantity',
        'actual_quantity',
        'waste_quantity',
        'total_cost',
        'produced_at',
        'produced_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'planned_quantity' => 'decimal:6',
            'actual_quantity' => 'decimal:6',
            'waste_quantity' => 'decimal:6',
            'total_cost' => 'decimal:2',
            'produced_at' => 'datetime',
        ];
    }

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProductionOrderItem::class);
    }
}
