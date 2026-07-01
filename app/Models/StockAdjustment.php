<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockAdjustment extends Model
{
    use BelongsToTenant, UsesUuid;

    protected $fillable = [
        'business_id',
        'branch_id',
        'warehouse_id',
        'uuid',
        'adjustment_number',
        'status',
        'notes',
        'posted_by',
        'posted_at',
    ];

    protected function casts(): array
    {
        return ['posted_at' => 'datetime'];
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockAdjustmentItem::class);
    }
}
