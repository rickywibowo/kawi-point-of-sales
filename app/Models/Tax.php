<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

class Tax extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id',
        'name',
        'code',
        'rate',
        'is_inclusive',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:4',
            'is_inclusive' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
