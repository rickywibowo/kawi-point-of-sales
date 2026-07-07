<?php

namespace Tests\Feature\Administration;

use App\Models\Branch;
use App\Models\Business;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class UserAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_user_access_directory(): void
    {
        $this->seed(DatabaseSeeder::class);
        [$owner, $business] = $this->context();

        $this->actingAs($owner, 'sanctum')
            ->withHeader('X-Business-Id', $business->uuid)
            ->getJson('/api/user-access')
            ->assertOk()
            ->assertJsonStructure(['users', 'roles', 'permissions', 'branches']);
    }

    public function test_owner_can_invite_user_and_assign_initial_role(): void
    {
        $this->seed(DatabaseSeeder::class);
        [$owner, $business, $branch] = $this->context();
        $cashierRole = Role::query()->whereNull('business_id')->where('slug', 'cashier')->firstOrFail();

        $this->actingAs($owner, 'sanctum')
            ->withHeader('X-Business-Id', $business->uuid)
            ->postJson('/api/user-access/users', [
                'name' => 'KAWI Cashier',
                'email' => 'cashier@kawi.test',
                'password' => 'password123',
                'branch_id' => $branch->id,
                'roles' => [
                    ['role_id' => $cashierRole->id, 'branch_id' => $branch->id],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('user.email', 'cashier@kawi.test');

        $cashier = User::query()->where('email', 'cashier@kawi.test')->firstOrFail();

        $this->assertTrue(Hash::check('password123', $cashier->password));
        $this->assertTrue($cashier->belongsToBusiness($business->id));
        $this->assertTrue($cashier->canInTenant('manage sales', $business->id, $branch->id));
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.invited']);
    }

    public function test_owner_can_assign_role_to_existing_business_user(): void
    {
        $this->seed(DatabaseSeeder::class);
        [$owner, $business, $branch] = $this->context();
        $accountantRole = Role::query()->whereNull('business_id')->where('slug', 'accounting')->firstOrFail();
        $user = User::query()->create([
            'name' => 'Accountant User',
            'email' => 'accountant@kawi.test',
            'password' => Hash::make('password'),
            'current_business_id' => $business->id,
        ]);
        $user->businesses()->syncWithoutDetaching([$business->id => ['is_owner' => false]]);

        $this->actingAs($owner, 'sanctum')
            ->withHeader('X-Business-Id', $business->uuid)
            ->postJson("/api/user-access/users/{$user->id}/roles", [
                'role_id' => $accountantRole->id,
                'branch_id' => $branch->id,
            ])
            ->assertOk();

        $this->assertTrue($user->fresh()->canInTenant('manage expense', $business->id, $branch->id));
        $this->assertDatabaseHas('audit_logs', ['action' => 'role.assigned']);
    }

    public function test_role_assignment_rejects_role_and_branch_from_other_business(): void
    {
        $this->seed(DatabaseSeeder::class);
        [$owner, $business] = $this->context();
        $outsideBusiness = Business::query()->create(['uuid' => (string) Str::uuid(), 'name' => 'Outside Business']);
        $outsideBranch = Branch::query()->create([
            'business_id' => $outsideBusiness->id,
            'uuid' => (string) Str::uuid(),
            'name' => 'Outside Branch',
            'code' => 'OUT',
        ]);
        $outsideRole = Role::query()->create([
            'business_id' => $outsideBusiness->id,
            'name' => 'Outside Role',
            'slug' => 'outside-role',
        ]);

        $this->actingAs($owner, 'sanctum')
            ->withHeader('X-Business-Id', $business->uuid)
            ->postJson('/api/user-access/users', [
                'name' => 'Invalid User',
                'email' => 'invalid-user@kawi.test',
                'branch_id' => $outsideBranch->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['branch_id']);

        $this->actingAs($owner, 'sanctum')
            ->withHeader('X-Business-Id', $business->uuid)
            ->postJson("/api/user-access/users/{$owner->id}/roles", [
                'role_id' => $outsideRole->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['role_id']);
    }

    private function context(): array
    {
        $owner = User::query()->where('email', 'owner@kawipos.local')->firstOrFail();
        $business = Business::query()->where('code', 'KCF')->firstOrFail();
        $branch = Branch::query()->where('business_id', $business->id)->where('code', 'KCF-01')->firstOrFail();

        return [$owner, $business, $branch];
    }
}
