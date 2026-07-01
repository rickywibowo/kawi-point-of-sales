<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Recipe extends Model
{
    use BelongsToBusiness, UsesUuid;

    protected $fillable = [
        'business_id',
        'product_id',
        'uuid',
        'name',
        'yield_quantity',
        'yield_unit_id',
        'waste_percentage',
        'computed_cost',
        'version',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'yield_quantity' => 'decimal:6',
            'waste_percentage' => 'decimal:4',
            'computed_cost' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function yieldUnit(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class, 'yield_unit_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(RecipeItem::class);
    }
}
