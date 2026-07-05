<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class Category extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id',
        'parent_id',
        'name',
        'slug',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (Category $category): void {
            if ($category->children()->exists()) {
                throw ValidationException::withMessages([
                    'category_id' => ['Category with child categories cannot be deleted.'],
                ]);
            }

            if ($category->products()->exists()) {
                throw ValidationException::withMessages([
                    'category_id' => ['Category with products cannot be deleted.'],
                ]);
            }
        });
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
