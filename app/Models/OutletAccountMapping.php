<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class OutletAccountMapping extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id',
        'outlet_id',
        'account_purpose',
        'account_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saving(function (OutletAccountMapping $mapping): void {
            $outletBusinessId = Branch::query()->whereKey($mapping->outlet_id)->value('business_id');
            $accountBusinessId = Account::query()->whereKey($mapping->account_id)->value('business_id');

            if ((int) $outletBusinessId !== (int) $mapping->business_id) {
                throw ValidationException::withMessages([
                    'outlet_id' => 'Outlet must belong to the mapping business.',
                ]);
            }

            if ((int) $accountBusinessId !== (int) $mapping->business_id) {
                throw ValidationException::withMessages([
                    'account_id' => 'Account must belong to the mapping business.',
                ]);
            }
        });
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'outlet_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
