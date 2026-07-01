<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    use BelongsToTenant, UsesUuid;

    protected $fillable = ['business_id', 'branch_id', 'uuid', 'name', 'code', 'type', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function stockBalances(): HasMany
    {
        return $this->hasMany(StockBalance::class);
    }
}
