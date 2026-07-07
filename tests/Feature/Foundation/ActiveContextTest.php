<?php

namespace Tests\Feature\Foundation;

use App\Models\Branch;
use App\Models\Business;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class ActiveContextTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_see_only_accessible_businesses_and_outlets(): void
    {
        $this->seed(DatabaseSeeder::class);
        [$user, $business, $outlet] = $this->limitedUser();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/me/context-options')
            ->assertOk()
            ->assertJsonCount(1, 'businesses')
            ->assertJsonPath('businesses.0.id', $business->id)
            ->assertJsonCount(1, 'businesses.0.outlets')
            ->assertJsonPath('businesses.0.outlets.0.id', $outlet->id);
    }

    public function test_user_cannot_set_inaccessible_business(): void
    {
        $this->seed(DatabaseSeeder::class);
        [$user] = $this->limitedUser();
        [$outsideBusiness, $outsideOutlet] = $this->outsideBusinessWithOutlet();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/me/active-context', [
                'business_id' => $outsideBusiness->id,
                'outlet_id' => $outsideOutlet->id,
            ])
            ->assertForbidden();
    }

    public function test_user_cannot_set_inaccessible_outlet(): void
    {
        $this->seed(DatabaseSeeder::class);
        [$user, $business] = $this->limitedUser();
        $blockedOutlet = Branch::query()->create([
            'business_id' => $business->id,
            'uuid' => (string) Str::uuid(),
            'name' => 'Blocked Outlet',
            'code' => 'BLOCK',
            'is_active' => true,
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/me/active-context', [
                'business_id' => $business->id,
                'outlet_id' => $blockedOutlet->id,
            ])
            ->assertForbidden();
    }

    public function test_user_cannot_set_outlet_from_another_business(): void
    {
        $this->seed(DatabaseSeeder::class);
        [$user, $business] = $this->limitedUser();
        [$outsideBusiness, $outsideOutlet] = $this->outsideBusinessWithOutlet();

        $user->businesses()->attach($outsideBusiness->id);
        $user->outlets()->attach($outsideOutlet->id);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/me/active-context', [
                'business_id' => $business->id,
                'outlet_id' => $outsideOutlet->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['outlet_id']);
    }

    public function test_active_context_can_be_retrieved_after_setting(): void
    {
        $this->seed(DatabaseSeeder::class);
        [$user, $business, $outlet] = $this->limitedUser();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/me/active-context', [
                'business_id' => $business->id,
                'outlet_id' => $outlet->id,
            ])
            ->assertOk();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/me/active-context')
            ->assertOk()
            ->assertJsonPath('active_business.id', $business->id)
            ->assertJsonPath('active_outlet.id', $outlet->id);
    }

    public function test_active_context_can_be_cleared(): void
    {
        $this->seed(DatabaseSeeder::class);
        [$user, $business, $outlet] = $this->limitedUser();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/me/active-context', [
                'business_id' => $business->id,
                'outlet_id' => $outlet->id,
            ])
            ->assertOk();

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/me/active-context')
            ->assertOk()
            ->assertJsonPath('active_business', null)
            ->assertJsonPath('active_outlet', null);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/me/active-context')
            ->assertOk()
            ->assertJsonPath('active_business', null)
            ->assertJsonPath('active_outlet', null);
    }

    private function limitedUser(): array
    {
        $business = Business::query()->where('code', 'KCF')->firstOrFail();
        $outlet = Branch::query()->where('business_id', $business->id)->where('code', 'KCF-01')->firstOrFail();
        $user = User::query()->create([
            'name' => 'Limited User',
            'email' => 'limited@kawipos.local',
            'password' => Hash::make('password'),
        ]);

        $user->businesses()->attach($business->id);
        $user->outlets()->attach($outlet->id);

        return [$user, $business, $outlet];
    }

    private function outsideBusinessWithOutlet(): array
    {
        $business = Business::query()->create([
            'uuid' => (string) Str::uuid(),
            'code' => 'OUT',
            'type' => 'restaurant',
            'name' => 'Outside Business',
            'is_active' => true,
        ]);
        $outlet = Branch::query()->create([
            'business_id' => $business->id,
            'uuid' => (string) Str::uuid(),
            'name' => 'Outside Outlet',
            'code' => 'OUT-01',
            'is_active' => true,
        ]);

        return [$business, $outlet];
    }
}
