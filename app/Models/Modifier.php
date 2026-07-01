<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Modifier extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id',
        'modifier_group_id',
        'name',
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

    public function modifierGroup(): BelongsTo
    {
        return $this->belongsTo(ModifierGroup::class);
    }
}
