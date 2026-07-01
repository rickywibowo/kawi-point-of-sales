<?php

namespace App\Models\Concerns;

use App\Models\Branch;
use App\Models\Business;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTenant
{
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function scopeForTenant(Builder $query, int $businessId, ?int $branchId = null): Builder
    {
        $query->where($query->qualifyColumn('business_id'), $businessId);

        if ($branchId !== null) {
            $query->where(function (Builder $query) use ($branchId): void {
                $query->whereNull($query->qualifyColumn('branch_id'))
                    ->orWhere($query->qualifyColumn('branch_id'), $branchId);
            });
        }

        return $query;
    }
}
