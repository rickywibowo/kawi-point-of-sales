<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecipeItem extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id',
        'recipe_id',
        'ingredient_product_id',
        'quantity',
        'unit_of_measure_id',
        'waste_percentage',
        'unit_cost',
        'line_cost',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:6',
            'waste_percentage' => 'decimal:4',
            'unit_cost' => 'decimal:2',
            'line_cost' => 'decimal:2',
        ];
    }

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    public function ingredientProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'ingredient_product_id');
    }

    public function unitOfMeasure(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class);
    }
}
