<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    use BelongsToBusiness, UsesUuid;

    protected $fillable = [
        'business_id',
        'product_id',
        'uuid',
        'name',
        'sku',
        'barcode',
        'price_delta',
        'cost_delta',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price_delta' => 'decimal:2',
            'cost_delta' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
