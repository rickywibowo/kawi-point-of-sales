<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerLoyaltyTransaction extends Model
{
    use BelongsToBusiness, UsesUuid;

    protected $fillable = [
        'business_id',
        'customer_id',
        'uuid',
        'type',
        'points_delta',
        'balance_after',
        'source_type',
        'source_id',
        'notes',
        'created_by',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
