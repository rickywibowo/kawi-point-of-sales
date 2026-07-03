<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TableReservation extends Model
{
    use BelongsToTenant, UsesUuid;

    protected $fillable = [
        'business_id',
        'branch_id',
        'dining_table_id',
        'customer_id',
        'uuid',
        'reservation_number',
        'guest_name',
        'guest_phone',
        'party_size',
        'reserved_at',
        'status',
        'notes',
        'seated_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'reserved_at' => 'datetime',
            'seated_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function diningTable(): BelongsTo
    {
        return $this->belongsTo(DiningTable::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
