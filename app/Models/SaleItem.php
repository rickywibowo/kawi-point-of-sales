<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SaleItem extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'business_id',
        'branch_id',
        'sale_id',
        'product_id',
        'product_variant_id',
        'product_name',
        'quantity',
        'unit_price',
        'discount_total',
        'tax_total',
        'line_total',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:6',
            'unit_price' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function modifiers(): HasMany
    {
        return $this->hasMany(SaleItemModifier::class);
    }
}
