<?php

namespace Tests\Feature\Foundation;

use App\Models\Branch;
use App\Models\Business;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RbacDemoUserSeederTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var array<int, array{email: string, role: string, business: string, outlet: string}>
     */
    private const DEMO_USERS = [
        ['email' => 'admin.kcf@kawipos.local', 'role' => 'admin', 'business' => 'KCF', 'outlet' => 'KCF-01'],
        ['email' => 'cashier.kcf@kawipos.local', 'role' => 'cashier', 'business' => 'KCF', 'outlet' => 'KCF-01'],
        ['email' => 'warehouse.kcf@kawipos.local', 'role' => 'warehouse', 'business' => 'KCF', 'outlet' => 'KCF-01'],
        ['email' => 'accounting.kcf@kawipos.local', 'role' => 'accounting', 'business' => 'KCF', 'outlet' => 'KCF-01'],
        ['email' => 'admin.wg@kawipos.local', 'role' => 'admin', 'business' => 'WG', 'outlet' => 'WG-01'],
        ['email' => 'cashier.wg@kawipos.local', 'role' => 'cashier', 'business' => 'WG', 'outlet' => 'WG-01'],
        ['email' => 'admin.lby@kawipos.local', 'role' => 'admin', 'business' => 'LBY', 'outlet' => 'LBY-01'],
        ['email' => 'cashier.lby@kawipos.local', 'role' => 'cashier', 'business' => 'LBY', 'outlet' => 'LBY-01'],
    ];

    public function test_demo_users_are_created(): void
    {
        $this->seed(DatabaseSeeder::class);

        foreach (self::DEMO_USERS as $demoUser) {
            $this->assertDatabaseHas('users', [
                'email' => $demoUser['email'],
            ]);
        }
    }

    public function test_each_demo_user_has_the_correct_role(): void
    {
        $this->seed(DatabaseSeeder::class);

        foreach (self::DEMO_USERS as $demoUser) {
            $user = User::query()->where('email', $demoUser['email'])->firstOrFail();

            $this->assertTrue(
                $user->roles()->where('slug', $demoUser['role'])->exists(),
                "{$demoUser['email']} should have {$demoUser['role']} role.",
            );
        }
    }

    public function test_admin_kcf_password_is_valid_after_seeding(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::query()->where('email', 'admin.kcf@kawipos.local')->firstOrFail();

        $this->assertTrue(Hash::check('password', $user->password));
    }

    public function test_admin_kcf_can_authenticate_successfully(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertTrue(Auth::attempt([
            'email' => 'admin.kcf@kawipos.local',
            'password' => 'password',
        ]));
    }

    public function test_each_demo_user_is_attached_only_to_the_correct_business(): void
    {
        $this->seed(DatabaseSeeder::class);

        foreach (self::DEMO_USERS as $demoUser) {
            $user = User::query()->where('email', $demoUser['email'])->firstOrFail();
            $business = Business::query()->where('code', $demoUser['business'])->firstOrFail();

            $this->assertSame([$business->id], $user->businesses()->pluck('businesses.id')->all());
        }
    }

    public function test_each_demo_user_is_attached_only_to_the_correct_outlet(): void
    {
        $this->seed(DatabaseSeeder::class);

        foreach (self::DEMO_USERS as $demoUser) {
            $user = User::query()->where('email', $demoUser['email'])->firstOrFail();
            $outlet = Branch::query()->where('code', $demoUser['outlet'])->firstOrFail();

            $this->assertSame([$outlet->id], $user->outlets()->pluck('branches.id')->all());
        }
    }

    public function test_owner_still_has_access_to_all_businesses_and_outlets(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::query()->where('email', 'owner@kawipos.local')->firstOrFail();

        $this->assertEqualsCanonicalizing(
            Business::query()->whereIn('code', ['KCF', 'WG', 'LBY'])->pluck('id')->all(),
            $owner->businesses()->pluck('businesses.id')->all(),
        );
        $this->assertEqualsCanonicalizing(
            Branch::query()->whereIn('code', ['KCF-01', 'WG-01', 'LBY-01'])->pluck('id')->all(),
            $owner->outlets()->pluck('branches.id')->all(),
        );
    }

    public function test_admin_kcf_can_only_see_and_select_kcf_context(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::query()->where('email', 'admin.kcf@kawipos.local')->firstOrFail();
        $business = Business::query()->where('code', 'KCF')->firstOrFail();
        $outlet = Branch::query()->where('code', 'KCF-01')->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/me/context-options')
            ->assertOk()
            ->assertJsonCount(1, 'businesses')
            ->assertJsonPath('businesses.0.id', $business->id)
            ->assertJsonCount(1, 'businesses.0.outlets')
            ->assertJsonPath('businesses.0.outlets.0.id', $outlet->id);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/me/active-context', [
                'business_id' => $business->id,
                'outlet_id' => $outlet->id,
            ])
            ->assertOk()
            ->assertJsonPath('active_business.id', $business->id)
            ->assertJsonPath('active_outlet.id', $outlet->id);
    }

    public function test_cashier_wg_can_only_see_and_select_wg_context(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::query()->where('email', 'cashier.wg@kawipos.local')->firstOrFail();
        $business = Business::query()->where('code', 'WG')->firstOrFail();
        $outlet = Branch::query()->where('code', 'WG-01')->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/me/context-options')
            ->assertOk()
            ->assertJsonCount(1, 'businesses')
            ->assertJsonPath('businesses.0.id', $business->id)
            ->assertJsonCount(1, 'businesses.0.outlets')
            ->assertJsonPath('businesses.0.outlets.0.id', $outlet->id);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/me/active-context', [
                'business_id' => $business->id,
                'outlet_id' => $outlet->id,
            ])
            ->assertOk()
            ->assertJsonPath('active_business.id', $business->id)
            ->assertJsonPath('active_outlet.id', $outlet->id);
    }
}
