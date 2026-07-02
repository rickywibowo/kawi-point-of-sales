<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    use BelongsToTenant, UsesUuid;

    protected $fillable = [
        'business_id',
        'branch_id',
        'cashier_shift_id',
        'customer_id',
        'cashier_id',
        'uuid',
        'sale_number',
        'idempotency_key',
        'type',
        'status',
        'subtotal',
        'discount_total',
        'tax_total',
        'service_charge_total',
        'grand_total',
        'paid_total',
        'change_total',
        'notes',
        'sold_at',
        'voided_at',
        'voided_by',
        'refunded_at',
        'refunded_by',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'service_charge_total' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'paid_total' => 'decimal:2',
            'change_total' => 'decimal:2',
            'sold_at' => 'datetime',
            'voided_at' => 'datetime',
            'refunded_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SalePayment::class);
    }

    public function cashierShift(): BelongsTo
    {
        return $this->belongsTo(CashierShift::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
