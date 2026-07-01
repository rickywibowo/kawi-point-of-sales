<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfflineSyncConflict extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'business_id',
        'branch_id',
        'offline_sync_batch_id',
        'client_uuid',
        'idempotency_key',
        'reason',
        'payload',
        'status',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return ['payload' => 'array', 'resolved_at' => 'datetime'];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(OfflineSyncBatch::class, 'offline_sync_batch_id');
    }
}
