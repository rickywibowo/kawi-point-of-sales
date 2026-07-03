<?php

namespace App\Services\Pos;

use App\Models\Branch;
use App\Models\Business;
use App\Models\DiningTable;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DiningTableService
{
    public function __construct(private readonly AuditLogger $audit)
    {
    }

    public function create(Business $business, Branch $branch, array $data, Request $request): DiningTable
    {
        $exists = DiningTable::query()
            ->where('branch_id', $branch->id)
            ->where('code', $data['code'])
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages(['code' => ['Dining table code already exists in this branch.']]);
        }

        return DB::transaction(function () use ($business, $branch, $data, $request): DiningTable {
            $table = DiningTable::query()->create([
                'business_id' => $business->id,
                'branch_id' => $branch->id,
                'code' => $data['code'],
                'name' => $data['name'],
                'capacity' => $data['capacity'] ?? 2,
                'section' => $data['section'] ?? null,
                'status' => $data['status'] ?? 'available',
            ]);

            $this->audit->record('dining_table.created', $table, after: $table->toArray(), request: $request);

            return $table;
        });
    }

    public function updateStatus(Business $business, Branch $branch, DiningTable $table, string $status, Request $request): DiningTable
    {
        abort_unless($table->business_id === $business->id && $table->branch_id === $branch->id, 403);

        $before = $table->toArray();
        $table->update(['status' => $status]);

        $this->audit->record('dining_table.status_updated', $table, before: $before, after: $table->fresh()->toArray(), request: $request);

        return $table->fresh();
    }
}
