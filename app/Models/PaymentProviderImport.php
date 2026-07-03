<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentProviderImport extends Model
{
    use BelongsToTenant, UsesUuid;

    protected $fillable = [
        'business_id',
        'branch_id',
        'payment_settlement_id',
        'imported_by',
        'uuid',
        'import_number',
        'provider',
        'method',
        'settlement_date',
        'row_count',
        'matched_count',
        'unmatched_count',
        'gross_amount',
        'fee_amount',
        'received_amount',
        'variance_to_settlement',
        'status',
        'notes',
        'imported_at',
    ];

    protected function casts(): array
    {
        return [
            'settlement_date' => 'date',
            'gross_amount' => 'decimal:2',
            'fee_amount' => 'decimal:2',
            'received_amount' => 'decimal:2',
            'variance_to_settlement' => 'decimal:2',
            'imported_at' => 'datetime',
        ];
    }

    public function settlement(): BelongsTo
    {
        return $this->belongsTo(PaymentSettlement::class, 'payment_settlement_id');
    }

    public function rows(): HasMany
    {
        return $this->hasMany(PaymentProviderImportRow::class);
    }
}
