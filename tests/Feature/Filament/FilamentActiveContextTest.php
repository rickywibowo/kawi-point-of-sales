<?php

namespace Tests\Feature\Filament;

use App\Models\Branch;
use App\Models\Business;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FilamentActiveContextTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_access_filament(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertUserCanAccessFilament('owner@kawipos.local');
    }

    public function test_admin_kcf_can_access_filament(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertUserCanAccessFilament('admin.kcf@kawipos.local');
    }

    public function test_cashier_kcf_can_access_filament(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertUserCanAccessFilament('cashier.kcf@kawipos.local');
    }

    public function test_warehouse_kcf_can_access_filament(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertUserCanAccessFilament('warehouse.kcf@kawipos.local');
    }

    public function test_accounting_kcf_can_access_filament(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertUserCanAccessFilament('accounting.kcf@kawipos.local');
    }

    public function test_user_without_role_cannot_access_filament(): void
    {
        $this->seed(DatabaseSeeder::class);

        $business = Business::query()->where('code', 'KCF')->firstOrFail();
        $user = User::query()->create([
            'name' => 'No Role User',
            'email' => 'no-role@kawipos.local',
            'password' => Hash::make('password'),
        ]);

        $user->businesses()->attach($business->id);

        $this->assertFalse($user->canAccessPanel(Filament::getPanel('admin')));
    }

    public function test_demo_user_without_context_is_auto_selected_and_can_continue_to_admin(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::query()->where('email', 'admin.kcf@kawipos.local')->firstOrFail();
        $business = Business::query()->where('code', 'KCF')->firstOrFail();
        $outlet = Branch::query()->where('business_id', $business->id)->where('code', 'KCF-01')->firstOrFail();

        $this->actingAs($user)
            ->get('/admin')
            ->assertOk()
            ->assertSessionHas('active_business_id', $business->id)
            ->assertSessionHas('active_outlet_id', $outlet->id);
    }

    public function test_cashier_wg_without_context_is_auto_selected_and_can_continue_to_admin(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::query()->where('email', 'cashier.wg@kawipos.local')->firstOrFail();
        $business = Business::query()->where('code', 'WG')->firstOrFail();
        $outlet = Branch::query()->where('business_id', $business->id)->where('code', 'WG-01')->firstOrFail();

        $this->actingAs($user)
            ->get('/admin')
            ->assertOk()
            ->assertSessionHas('active_business_id', $business->id)
            ->assertSessionHas('active_outlet_id', $outlet->id);
    }

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

    public function test_owner_with_multiple_businesses_is_not_auto_selected_to_arbitrary_business(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::query()->where('email', 'owner@kawipos.local')->firstOrFail();

        $this->actingAs($user)
            ->get('/admin')
            ->assertRedirect(route('filament.admin.pages.manage-active-context'));
    }

    public function test_owner_can_change_business_from_header(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::query()->where('email', 'owner@kawipos.local')->firstOrFail();
        $business = Business::query()->where('code', 'WG')->firstOrFail();
        $outlet = Branch::query()->where('business_id', $business->id)->where('code', 'WG-01')->firstOrFail();

        $this->actingAs($user)
            ->from('/admin')
            ->post(route('filament.active-context.header-switch'), [
                'business_id' => $business->id,
            ])
            ->assertRedirect('/admin')
            ->assertSessionHas('active_business_id', $business->id)
            ->assertSessionHas('active_outlet_id', $outlet->id);
    }

    public function test_header_outlet_options_are_filtered_by_selected_business(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::query()->where('email', 'owner@kawipos.local')->firstOrFail();
        $business = Business::query()->where('code', 'KCF')->firstOrFail();
        $outlet = Branch::query()->where('business_id', $business->id)->where('code', 'KCF-01')->firstOrFail();

        $this->actingAs($user)
            ->withSession([
                'active_business_id' => $business->id,
                'active_outlet_id' => $outlet->id,
            ])
            ->get('/admin')
            ->assertOk()
            ->assertSee('Kawi Chinese Food')
            ->assertSee('Main Outlet')
            ->assertDontSee('Kawi Chinese Food - Main Outlet')
            ->assertDontSee('KCF-01')
            ->assertDontSee('WG-01')
            ->assertDontSee('LBY-01');
    }

    public function test_user_cannot_select_inaccessible_business_from_header(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::query()->where('email', 'admin.kcf@kawipos.local')->firstOrFail();
        $business = Business::query()->where('code', 'WG')->firstOrFail();

        $this->actingAs($user)
            ->post(route('filament.active-context.header-switch'), [
                'business_id' => $business->id,
            ])
            ->assertForbidden();
    }

    public function test_user_cannot_select_inaccessible_outlet_from_header(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::query()->where('email', 'admin.kcf@kawipos.local')->firstOrFail();
        $business = Business::query()->where('code', 'KCF')->firstOrFail();
        $outlet = Branch::query()->where('code', 'WG-01')->firstOrFail();

        $this->actingAs($user)
            ->post(route('filament.active-context.header-switch'), [
                'business_id' => $business->id,
                'outlet_id' => $outlet->id,
            ])
            ->assertForbidden();
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

    private function assertUserCanAccessFilament(string $email): void
    {
        $user = User::query()->where('email', $email)->firstOrFail();

        $this->assertTrue($user->canAccessPanel(Filament::getPanel('admin')));
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
