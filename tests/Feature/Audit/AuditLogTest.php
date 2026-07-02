<?php

namespace Tests\Feature\Audit;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Business;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_review_audit_logs_with_summary(): void
    {
        $this->seed(DatabaseSeeder::class);
        [$user, $business, $branch] = $this->context();
        $product = Product::query()->where('business_id', $business->id)->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->withHeader('X-Business-Id', $business->uuid)
            ->postJson('/api/categories', ['name' => 'Audit Category'])
            ->assertCreated();

        AuditLog::query()->create([
            'user_id' => $user->id,
            'business_id' => $business->id,
            'branch_id' => $branch->id,
            'action' => 'custom.audit.test',
            'entity_type' => Product::class,
            'entity_id' => $product->id,
            'after_values' => ['name' => $product->name],
            'ip_address' => '127.0.0.1',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Business-Id' => $business->uuid, 'X-Branch-Id' => $branch->uuid])
            ->getJson('/api/audit-logs')
            ->assertOk()
            ->assertJsonStructure([
                'summary' => ['total_events', 'unique_users', 'actions', 'recent_security_events'],
                'audit_logs' => ['data'],
            ])
            ->json();

        $this->assertGreaterThanOrEqual(2, $response['summary']['total_events']);
        $this->assertStringContainsString('custom.audit.test', json_encode($response));
    }

    public function test_audit_log_filter_by_action(): void
    {
        $this->seed(DatabaseSeeder::class);
        [$user, $business] = $this->context();

        AuditLog::query()->create([
            'user_id' => $user->id,
            'business_id' => $business->id,
            'action' => 'audit.filter.target',
        ]);
        AuditLog::query()->create([
            'user_id' => $user->id,
            'business_id' => $business->id,
            'action' => 'audit.filter.other',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->withHeader('X-Business-Id', $business->uuid)
            ->getJson('/api/audit-logs?action=audit.filter.target')
            ->assertOk()
            ->json('audit_logs.data');

        $this->assertCount(1, $response);
        $this->assertSame('audit.filter.target', $response[0]['action']);
    }

    public function test_audit_logs_do_not_leak_other_business_data(): void
    {
        $this->seed(DatabaseSeeder::class);
        [$user, $business] = $this->context();
        $outsideBusiness = Business::query()->create(['uuid' => (string) Str::uuid(), 'name' => 'Outside Business']);

        AuditLog::query()->create([
            'business_id' => $business->id,
            'user_id' => $user->id,
            'action' => 'inside.audit',
        ]);
        AuditLog::query()->create([
            'business_id' => $outsideBusiness->id,
            'action' => 'outside.audit',
        ]);

        $content = $this->actingAs($user, 'sanctum')
            ->withHeader('X-Business-Id', $business->uuid)
            ->getJson('/api/audit-logs')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('inside.audit', $content);
        $this->assertStringNotContainsString('outside.audit', $content);
    }

    private function context(): array
    {
        $user = User::query()->where('email', 'owner@kawi.test')->firstOrFail();
        $business = Business::query()->where('name', 'KAWI Demo Business')->firstOrFail();
        $branch = Branch::query()->where('business_id', $business->id)->where('code', 'MAIN')->firstOrFail();

        return [$user, $business, $branch];
    }
}
