<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashMovement extends Model
{
    use BelongsToTenant;

    protected $fillable = ['business_id', 'branch_id', 'cashier_shift_id', 'user_id', 'type', 'amount', 'reason'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }

    public function cashierShift(): BelongsTo
    {
        return $this->belongsTo(CashierShift::class);
    }
}
