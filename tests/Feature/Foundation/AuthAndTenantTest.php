<?php

namespace Tests\Feature\Foundation;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Business;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\KawiFoundationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuthAndTenantTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_sanctum_token(): void
    {
        $this->seed(KawiFoundationSeeder::class);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'owner@kawi.test',
            'password' => 'password',
            'device_name' => 'feature-test',
        ]);

        $response
            ->assertOk()
            ->assertJsonStructure(['token', 'user' => ['id', 'email', 'businesses', 'roles']]);

        $this->assertDatabaseHas('personal_access_tokens', ['name' => 'feature-test']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'login']);
    }

    public function test_tenant_middleware_rejects_business_outside_membership(): void
    {
        $this->seed(KawiFoundationSeeder::class);

        $user = User::query()->where('email', 'owner@kawi.test')->firstOrFail();
        $outsideBusiness = Business::query()->create(['name' => 'Outside Tenant']);

        $this->actingAs($user, 'sanctum')
            ->withHeader('X-Business-Id', $outsideBusiness->uuid)
            ->getJson('/api/auth/me')
            ->assertForbidden();
    }

    public function test_permission_middleware_allows_role_permission_in_tenant_scope(): void
    {
        $this->seed(KawiFoundationSeeder::class);

        $user = User::query()->where('email', 'owner@kawi.test')->firstOrFail();
        $business = Business::query()->where('name', 'KAWI Demo Business')->firstOrFail();
        $branch = Branch::query()->where('business_id', $business->id)->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->withHeaders([
                'X-Business-Id' => $business->uuid,
                'X-Branch-Id' => $branch->uuid,
            ])
            ->getJson('/api/foundation/permissions/reports')
            ->assertOk()
            ->assertJson(['allowed' => true]);
    }

    public function test_logout_records_audit_log_and_revokes_current_token(): void
    {
        $this->seed(KawiFoundationSeeder::class);

        $login = $this->postJson('/api/auth/login', [
            'email' => 'owner@kawi.test',
            'password' => 'password',
            'device_name' => 'logout-test',
        ])->json();

        $business = Business::query()->where('name', 'KAWI Demo Business')->firstOrFail();

        $this->withToken($login['token'])
            ->withHeader('X-Business-Id', $business->uuid)
            ->postJson('/api/auth/logout')
            ->assertOk();

        $this->assertSame(1, AuditLog::query()->where('action', 'logout')->count());
        $this->assertDatabaseMissing('personal_access_tokens', ['name' => 'logout-test']);
    }

    public function test_owner_can_switch_business_branch_context(): void
    {
        $this->seed(KawiFoundationSeeder::class);

        $user = User::query()->where('email', 'owner@kawi.test')->firstOrFail();
        $business = Business::query()->where('name', 'KAWI Demo Business')->firstOrFail();
        $branch = Branch::query()->create([
            'business_id' => $business->id,
            'uuid' => (string) Str::uuid(),
            'name' => 'Cabang Kedua',
            'code' => 'BR2',
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/auth/context', [
                'business_id' => $business->id,
                'branch_id' => $branch->id,
            ])
            ->assertOk()
            ->assertJsonPath('business.id', $business->id)
            ->assertJsonPath('branch.id', $branch->id);

        $this->assertSame($branch->id, $user->fresh()->current_branch_id);
        $this->assertDatabaseHas('audit_logs', ['action' => 'auth.context_switched']);
    }

    public function test_branch_scoped_user_cannot_select_unassigned_branch(): void
    {
        $this->seed(KawiFoundationSeeder::class);

        $business = Business::query()->where('name', 'KAWI Demo Business')->firstOrFail();
        $allowedBranch = Branch::query()->where('business_id', $business->id)->firstOrFail();
        $blockedBranch = Branch::query()->create([
            'business_id' => $business->id,
            'uuid' => (string) Str::uuid(),
            'name' => 'Cabang Terlarang',
            'code' => 'NOPE',
        ]);
        $cashierRole = Role::query()->where('business_id', $business->id)->where('slug', 'cashier')->firstOrFail();
        $cashier = User::query()->create([
            'name' => 'Branch Cashier',
            'email' => 'branch-cashier@kawi.test',
            'password' => Hash::make('password'),
            'current_business_id' => $business->id,
            'current_branch_id' => $allowedBranch->id,
        ]);
        $cashier->businesses()->syncWithoutDetaching([$business->id => ['is_owner' => false]]);
        $cashier->roles()->syncWithoutDetaching([
            $cashierRole->id => [
                'business_id' => $business->id,
                'branch_id' => $allowedBranch->id,
            ],
        ]);

        $this->actingAs($cashier, 'sanctum')
            ->postJson('/api/auth/context', [
                'business_id' => $business->id,
                'branch_id' => $blockedBranch->id,
            ])
            ->assertForbidden();

        $this->actingAs($cashier, 'sanctum')
            ->withHeaders([
                'X-Business-Id' => $business->uuid,
                'X-Branch-Id' => $blockedBranch->uuid,
            ])
            ->getJson('/api/auth/me')
            ->assertForbidden();
    }
}
