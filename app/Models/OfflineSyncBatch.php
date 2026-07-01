<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OfflineSyncBatch extends Model
{
    use BelongsToTenant, UsesUuid;

    protected $fillable = [
        'business_id',
        'branch_id',
        'user_id',
        'uuid',
        'batch_key',
        'status',
        'received_count',
        'synced_count',
        'conflict_count',
        'processed_at',
    ];

    protected function casts(): array
    {
        return ['processed_at' => 'datetime'];
    }

    public function conflicts(): HasMany
    {
        return $this->hasMany(OfflineSyncConflict::class);
    }
}
