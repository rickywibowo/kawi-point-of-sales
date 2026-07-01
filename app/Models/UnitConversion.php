<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnitConversion extends Model
{
    use BelongsToBusiness;

    protected $fillable = ['business_id', 'from_unit_id', 'to_unit_id', 'multiplier', 'is_active'];

    protected function casts(): array
    {
        return ['multiplier' => 'decimal:6', 'is_active' => 'boolean'];
    }

    public function fromUnit(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class, 'from_unit_id');
    }

    public function toUnit(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class, 'to_unit_id');
    }
}
