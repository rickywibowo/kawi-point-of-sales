<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'current_business_id', 'current_branch_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function businesses(): BelongsToMany
    {
        return $this->belongsToMany(Business::class)->withPivot('is_owner')->withTimestamps();
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withPivot(['business_id', 'branch_id'])->withTimestamps();
    }

    public function currentBusiness(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'current_business_id');
    }

    public function currentBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'current_branch_id');
    }

    public function belongsToBusiness(int $businessId): bool
    {
        return $this->businesses()->whereKey($businessId)->exists();
    }

    public function canInTenant(string $permission, int $businessId, ?int $branchId = null): bool
    {
        return $this->roles()
            ->where(function ($query) use ($businessId): void {
                $query->whereNull('role_user.business_id')
                    ->orWhere('role_user.business_id', $businessId);
            })
            ->where(function ($query) use ($branchId): void {
                $query->whereNull('role_user.branch_id');

                if ($branchId !== null) {
                    $query->orWhere('role_user.branch_id', $branchId);
                }
            })
            ->whereHas('permissions', fn ($query) => $query->where('name', $permission))
            ->exists();
    }
}
