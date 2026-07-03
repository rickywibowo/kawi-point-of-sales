<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SalePayment extends Model
{
    use BelongsToTenant, UsesUuid;

    protected $fillable = ['business_id', 'branch_id', 'sale_id', 'uuid', 'method', 'amount', 'reference', 'metadata'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'metadata' => 'array'];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function settlementItem(): HasOne
    {
        return $this->hasOne(PaymentSettlementItem::class);
    }
}
