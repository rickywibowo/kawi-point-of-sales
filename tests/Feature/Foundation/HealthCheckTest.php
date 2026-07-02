<?php

namespace Tests\Feature\Foundation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_health_endpoint_reports_runtime_and_database_status(): void
    {
        $this->getJson('/api/health')
            ->assertOk()
            ->assertJson([
                'status' => 'ok',
                'checks' => [
                    'database' => [
                        'status' => 'ok',
                    ],
                ],
            ])
            ->assertJsonStructure([
                'status',
                'checks' => [
                    'app' => ['status', 'environment', 'debug', 'timezone'],
                    'database' => ['status', 'connection'],
                    'runtime' => ['status', 'php', 'queue', 'cache'],
                    'release' => ['status', 'name', 'version', 'commit', 'channel'],
                ],
            ]);
    }
}
