<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItemModifier extends Model
{
    protected $fillable = ['sale_item_id', 'modifier_id', 'modifier_name', 'price_delta'];

    protected function casts(): array
    {
        return ['price_delta' => 'decimal:2'];
    }

    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(SaleItem::class);
    }
}
