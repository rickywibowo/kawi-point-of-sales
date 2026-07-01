<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Model;

class HeldTransaction extends Model
{
    use BelongsToTenant, UsesUuid;

    protected $fillable = [
        'business_id',
        'branch_id',
        'cashier_shift_id',
        'customer_id',
        'cashier_id',
        'uuid',
        'hold_number',
        'payload',
        'held_at',
        'resumed_at',
    ];

    protected function casts(): array
    {
        return ['payload' => 'array', 'held_at' => 'datetime', 'resumed_at' => 'datetime'];
    }
}
