<?php

namespace App\Services\Offline;

use App\Models\Branch;
use App\Models\Business;
use App\Models\OfflineSyncBatch;
use App\Models\OfflineSyncConflict;
use App\Models\Sale;
use App\Services\Audit\AuditLogger;
use App\Services\Pos\PosService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class OfflineSyncService
{
    public function __construct(
        private readonly PosService $pos,
        private readonly AuditLogger $audit,
    ) {
    }

    public function syncSales(Business $business, Branch $branch, array $data, Request $request): OfflineSyncBatch
    {
        return DB::transaction(function () use ($business, $branch, $data, $request): OfflineSyncBatch {
            $batch = OfflineSyncBatch::query()->firstOrCreate(
                ['business_id' => $business->id, 'batch_key' => $data['batch_key']],
                [
                    'branch_id' => $branch->id,
                    'user_id' => $request->user()?->id,
                    'uuid' => (string) Str::uuid(),
                    'status' => 'processing',
                    'received_count' => count($data['sales']),
                    'processed_at' => now(),
                ],
            );

            if ($batch->wasRecentlyCreated === false && $batch->status === 'processed') {
                return $batch->load('conflicts');
            }

            $synced = 0;
            $conflicts = 0;

            foreach ($data['sales'] as $saleEnvelope) {
                $payload = $saleEnvelope['payload'];
                $payload['idempotency_key'] ??= $payload['offline_uuid'] ?? $saleEnvelope['client_uuid'];

                $existing = Sale::query()
                    ->where('business_id', $business->id)
                    ->where('idempotency_key', $payload['idempotency_key'])
                    ->first();

                if ($existing) {
                    $synced++;
                    continue;
                }

                try {
                    $this->pos->completeSale($business, $branch, $payload, $request);
                    $synced++;
                } catch (Throwable $exception) {
                    OfflineSyncConflict::query()->create([
                        'business_id' => $business->id,
                        'branch_id' => $branch->id,
                        'offline_sync_batch_id' => $batch->id,
                        'client_uuid' => $saleEnvelope['client_uuid'],
                        'idempotency_key' => $payload['idempotency_key'] ?? null,
                        'reason' => $exception->getMessage(),
                        'payload' => $payload,
                        'status' => 'open',
                    ]);
                    $conflicts++;
                }
            }

            $batch->update([
                'status' => $conflicts > 0 ? 'conflict' : 'processed',
                'received_count' => count($data['sales']),
                'synced_count' => $synced,
                'conflict_count' => $conflicts,
                'processed_at' => now(),
            ]);

            $batch->load('conflicts');
            $this->audit->record('offline_sales.synced', $batch, after: $batch->toArray(), request: $request);

            return $batch;
        });
    }
}
