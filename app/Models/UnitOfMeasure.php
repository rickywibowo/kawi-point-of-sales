<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

class UnitOfMeasure extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id',
        'name',
        'code',
        'type',
        'base_multiplier',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'base_multiplier' => 'decimal:6',
            'is_active' => 'boolean',
        ];
    }
}
