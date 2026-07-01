<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

trait UsesUuid
{
    protected static function bootUsesUuid(): void
    {
        static::creating(function ($model): void {
            if (! $model->uuid) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }
}
