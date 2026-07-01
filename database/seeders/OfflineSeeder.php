<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Business;
use App\Models\OfflineSyncBatch;
use App\Models\OfflineSyncConflict;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class OfflineSeeder extends Seeder
{
    public function run(): void
    {
        $business = Business::query()->where('name', 'KAWI Demo Business')->firstOrFail();
        $branch = Branch::query()->where('business_id', $business->id)->where('code', 'MAIN')->firstOrFail();
        $owner = User::query()->where('email', 'owner@kawi.test')->firstOrFail();

        $batch = OfflineSyncBatch::query()->firstOrCreate(
            ['business_id' => $business->id, 'batch_key' => 'BATCH-SEED-001'],
            [
                'branch_id' => $branch->id,
                'user_id' => $owner->id,
                'uuid' => (string) Str::uuid(),
                'status' => 'conflict',
                'received_count' => 1,
                'synced_count' => 0,
                'conflict_count' => 1,
                'processed_at' => now(),
            ],
        );

        OfflineSyncConflict::query()->firstOrCreate(
            ['offline_sync_batch_id' => $batch->id, 'client_uuid' => 'offline-seed-conflict'],
            [
                'business_id' => $business->id,
                'branch_id' => $branch->id,
                'idempotency_key' => 'offline-seed-conflict',
                'reason' => 'Seed conflict for review workflow',
                'payload' => ['sale_number' => 'OFFLINE-CONFLICT'],
                'status' => 'open',
            ],
        );
    }
}
