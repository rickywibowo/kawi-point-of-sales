<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiningTable extends Model
{
    use BelongsToTenant, UsesUuid;

    protected $fillable = [
        'business_id',
        'branch_id',
        'uuid',
        'code',
        'name',
        'capacity',
        'section',
        'status',
    ];

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }
}
