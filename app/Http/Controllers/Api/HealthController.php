<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $checks = [
            'app' => [
                'status' => 'ok',
                'environment' => app()->environment(),
                'debug' => config('app.debug'),
                'timezone' => config('app.timezone'),
            ],
            'database' => $this->databaseCheck(),
            'runtime' => [
                'status' => 'ok',
                'php' => PHP_VERSION,
                'queue' => config('queue.default'),
                'cache' => config('cache.default'),
            ],
            'release' => [
                'status' => 'ok',
                'name' => config('app.name'),
                'version' => env('KAWI_APP_VERSION', 'local'),
                'commit' => env('KAWI_BUILD_SHA'),
                'channel' => env('KAWI_RELEASE_CHANNEL', app()->environment()),
            ],
        ];

        $healthy = collect($checks)->every(fn (array $check): bool => $check['status'] === 'ok');

        return response()->json([
            'status' => $healthy ? 'ok' : 'degraded',
            'checks' => $checks,
        ], $healthy ? 200 : 503);
    }

    private function databaseCheck(): array
    {
        try {
            DB::select('select 1');

            return [
                'status' => 'ok',
                'connection' => config('database.default'),
            ];
        } catch (Throwable $exception) {
            return [
                'status' => 'failed',
                'connection' => config('database.default'),
                'message' => $exception->getMessage(),
            ];
        }
    }
}
