<?php

namespace Tests\Feature\Foundation;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Business;
use App\Models\User;
use Database\Seeders\KawiFoundationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
