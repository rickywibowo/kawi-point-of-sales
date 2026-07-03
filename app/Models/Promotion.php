<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Promotion extends Model
{
    use BelongsToBusiness, UsesUuid;

    protected $fillable = [
        'business_id',
        'uuid',
        'code',
        'name',
        'type',
        'value',
        'minimum_subtotal',
        'maximum_discount',
        'usage_limit',
        'usage_count',
        'starts_on',
        'ends_on',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'minimum_subtotal' => 'decimal:2',
            'maximum_discount' => 'decimal:2',
            'starts_on' => 'date',
            'ends_on' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }
}
