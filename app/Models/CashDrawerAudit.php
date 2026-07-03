<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashDrawerAudit extends Model
{
    use BelongsToTenant, UsesUuid;

    protected $fillable = [
        'business_id',
        'branch_id',
        'cashier_shift_id',
        'user_id',
        'uuid',
        'denomination_breakdown',
        'expected_cash',
        'counted_cash',
        'variance_amount',
        'status',
        'variance_reason',
        'approved_by',
        'audited_at',
    ];

    protected function casts(): array
    {
        return [
            'denomination_breakdown' => 'array',
            'expected_cash' => 'decimal:2',
            'counted_cash' => 'decimal:2',
            'variance_amount' => 'decimal:2',
            'audited_at' => 'datetime',
        ];
    }

    public function cashierShift(): BelongsTo
    {
        return $this->belongsTo(CashierShift::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
