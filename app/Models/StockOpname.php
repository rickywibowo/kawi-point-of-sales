<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Model;

class StockOpname extends Model
{
    use BelongsToTenant, UsesUuid;

    protected $fillable = [
        'business_id',
        'branch_id',
        'warehouse_id',
        'uuid',
        'opname_number',
        'status',
        'counted_at',
        'counted_by',
    ];

    protected function casts(): array
    {
        return ['counted_at' => 'datetime'];
    }
}
