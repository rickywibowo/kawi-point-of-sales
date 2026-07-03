<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentSettlement extends Model
{
    use BelongsToTenant, UsesUuid;

    protected $fillable = [
        'business_id',
        'branch_id',
        'uuid',
        'settlement_number',
        'method',
        'date_from',
        'date_to',
        'expected_amount',
        'reported_amount',
        'variance_amount',
        'status',
        'notes',
        'posted_at',
        'posted_by',
    ];

    protected function casts(): array
    {
        return [
            'date_from' => 'date',
            'date_to' => 'date',
            'expected_amount' => 'decimal:2',
            'reported_amount' => 'decimal:2',
            'variance_amount' => 'decimal:2',
            'posted_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(PaymentSettlementItem::class);
    }

    public function providerImports(): HasMany
    {
        return $this->hasMany(PaymentProviderImport::class);
    }
}
