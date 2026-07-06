<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'current_business_id', 'current_branch_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable {
        HasRoles::roles as spatieRoles;
    }

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
        return $this->morphToMany(Role::class, 'model', 'model_has_roles', 'model_id', 'role_id')
            ->withPivot(['business_id', 'branch_id'])
            ->withTimestamps();
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
        return $this->isPlatformSuperAdmin() || $this->businesses()->whereKey($businessId)->exists();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isPlatformSuperAdmin() || $this->businesses()->exists();
    }

    public function isPlatformSuperAdmin(): bool
    {
        return $this->roles()->where('slug', 'platform-super-admin')->exists();
    }

    public function isBusinessOwner(int $businessId): bool
    {
        return $this->businesses()
            ->whereKey($businessId)
            ->wherePivot('is_owner', true)
            ->exists();
    }

    public function hasBusinessLevelRole(int $businessId): bool
    {
        return $this->roles()
            ->where(function ($query) use ($businessId): void {
                $query->whereNull('model_has_roles.business_id')
                    ->orWhere('model_has_roles.business_id', $businessId);
            })
            ->whereNull('model_has_roles.branch_id')
            ->exists();
    }

    public function canAccessBranchContext(int $businessId, ?int $branchId): bool
    {
        if ($this->isPlatformSuperAdmin()) {
            return true;
        }

        if (! $this->businesses()->whereKey($businessId)->exists()) {
            return false;
        }

        if ($branchId === null) {
            return $this->isBusinessOwner($businessId) || $this->hasBusinessLevelRole($businessId);
        }

        return $this->isBusinessOwner($businessId)
            || $this->hasBusinessLevelRole($businessId)
            || $this->roles()
                ->where('model_has_roles.business_id', $businessId)
                ->where('model_has_roles.branch_id', $branchId)
                ->exists();
    }

    public function canInTenant(string $permission, int $businessId, ?int $branchId = null): bool
    {
        return $this->roles()
            ->where(function ($query) use ($businessId): void {
                $query->whereNull('model_has_roles.business_id')
                    ->orWhere('model_has_roles.business_id', $businessId);
            })
            ->where(function ($query) use ($branchId): void {
                $query->whereNull('model_has_roles.branch_id');

                if ($branchId !== null) {
                    $query->orWhere('model_has_roles.branch_id', $branchId);
                }
            })
            ->whereHas('permissions', fn ($query) => $query->where('name', $permission))
            ->exists();
    }
}
