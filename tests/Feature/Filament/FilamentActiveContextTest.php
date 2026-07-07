<?php

namespace Tests\Feature\Filament;

use App\Models\Branch;
use App\Models\Business;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FilamentActiveContextTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_select_kcf_context(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertOwnerCanSelect('KCF', 'KCF-01');
    }

    public function test_owner_can_select_wg_context(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertOwnerCanSelect('WG', 'WG-01');
    }

    public function test_owner_can_select_lby_context(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertOwnerCanSelect('LBY', 'LBY-01');
    }

    public function test_inaccessible_business_cannot_be_selected(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = $this->limitedUser();
        $business = Business::query()->where('code', 'WG')->firstOrFail();
        $outlet = Branch::query()->where('business_id', $business->id)->where('code', 'WG-01')->firstOrFail();

        $this->actingAs($user)
            ->post('/admin/active-context', [
                'business_id' => $business->id,
                'outlet_id' => $outlet->id,
            ])
            ->assertForbidden();
    }

    public function test_outlet_from_another_business_cannot_be_selected(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::query()->where('email', 'owner@kawipos.local')->firstOrFail();
        $business = Business::query()->where('code', 'KCF')->firstOrFail();
        $outlet = Branch::query()->where('code', 'WG-01')->firstOrFail();

        $this->actingAs($user)
            ->post('/admin/active-context', [
                'business_id' => $business->id,
                'outlet_id' => $outlet->id,
            ])
            ->assertStatus(422);
    }

    public function test_invalid_active_context_is_cleared_and_redirected(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::query()->where('email', 'owner@kawipos.local')->firstOrFail();

        $this->actingAs($user)
            ->withSession([
                'active_business_id' => 999999,
                'active_outlet_id' => 999999,
            ])
            ->get('/admin')
            ->assertRedirect(route('filament.admin.pages.manage-active-context'))
            ->assertSessionMissing('active_business_id')
            ->assertSessionMissing('active_outlet_id');
    }

    public function test_user_without_context_is_redirected_to_manage_active_context(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::query()->where('email', 'owner@kawipos.local')->firstOrFail();

        $this->actingAs($user)
            ->get('/admin')
            ->assertRedirect(route('filament.admin.pages.manage-active-context'));
    }

    private function assertOwnerCanSelect(string $businessCode, string $outletCode): void
    {
        $user = User::query()->where('email', 'owner@kawipos.local')->firstOrFail();
        $business = Business::query()->where('code', $businessCode)->firstOrFail();
        $outlet = Branch::query()->where('business_id', $business->id)->where('code', $outletCode)->firstOrFail();

        $this->actingAs($user)
            ->post('/admin/active-context', [
                'business_id' => $business->id,
                'outlet_id' => $outlet->id,
            ])
            ->assertRedirect(route('filament.admin.pages.manage-active-context'))
            ->assertSessionHas('active_business_id', $business->id)
            ->assertSessionHas('active_outlet_id', $outlet->id);
    }

    private function limitedUser(): User
    {
        $business = Business::query()->where('code', 'KCF')->firstOrFail();
        $outlet = Branch::query()->where('business_id', $business->id)->where('code', 'KCF-01')->firstOrFail();
        $user = User::query()->create([
            'name' => 'Filament Limited User',
            'email' => 'filament-limited@kawipos.local',
            'password' => Hash::make('password'),
        ]);

        $user->businesses()->attach($business->id);
        $user->outlets()->attach($outlet->id);

        return $user;
    }
}
