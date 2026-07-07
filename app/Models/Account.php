<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

class Account extends Model
{
    use BelongsToBusiness, SoftDeletes;

    protected $fillable = [
        'business_id',
        'parent_id',
        'code',
        'name',
        'type',
        'normal_balance',
        'is_cash',
        'is_cash_account',
        'is_system',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_cash' => 'boolean',
            'is_cash_account' => 'boolean',
            'is_system' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Account $account): void {
            if (! $account->parent_id) {
                return;
            }

            $parentBusinessId = Account::query()->whereKey($account->parent_id)->value('business_id');

            if ((int) $parentBusinessId !== (int) $account->business_id) {
                throw ValidationException::withMessages([
                    'parent_id' => 'Parent account must belong to the same business.',
                ]);
            }
        });
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Account::class, 'parent_id');
    }

    public function journalLines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }

    public function outletAccountMappings(): HasMany
    {
        return $this->hasMany(OutletAccountMapping::class);
    }
}
