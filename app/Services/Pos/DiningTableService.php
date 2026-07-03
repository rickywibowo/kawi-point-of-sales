<?php

namespace App\Services\Pos;

use App\Models\Branch;
use App\Models\Business;
use App\Models\Customer;
use App\Models\DiningTable;
use App\Models\TableReservation;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Carbon;
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

    public function reserve(Business $business, Branch $branch, DiningTable $table, array $data, Request $request): TableReservation
    {
        abort_unless($table->business_id === $business->id && $table->branch_id === $branch->id, 403);

        if (! in_array($table->status, ['available', 'reserved'], true)) {
            throw ValidationException::withMessages(['dining_table_id' => ['Only available or reserved tables can receive reservations.']]);
        }

        if ((int) $data['party_size'] > (int) $table->capacity) {
            throw ValidationException::withMessages(['party_size' => ['Party size cannot exceed table capacity.']]);
        }

        if (! empty($data['customer_id']) && ! Customer::query()->forBusiness($business->id)->whereKey($data['customer_id'])->exists()) {
            throw ValidationException::withMessages(['customer_id' => ['The selected customer is outside the active business.']]);
        }

        $reservedAt = Carbon::parse($data['reserved_at']);
        $windowStart = $reservedAt->copy()->subHours(2);
        $windowEnd = $reservedAt->copy()->addHours(2);
        $overlapExists = TableReservation::query()
            ->where('dining_table_id', $table->id)
            ->whereIn('status', ['booked', 'seated'])
            ->whereBetween('reserved_at', [$windowStart, $windowEnd])
            ->exists();

        if ($overlapExists) {
            throw ValidationException::withMessages(['reserved_at' => ['This table already has an active reservation near that time.']]);
        }

        return DB::transaction(function () use ($business, $branch, $table, $data, $reservedAt, $request): TableReservation {
            $reservation = TableReservation::query()->create([
                'business_id' => $business->id,
                'branch_id' => $branch->id,
                'dining_table_id' => $table->id,
                'customer_id' => $data['customer_id'] ?? null,
                'reservation_number' => $data['reservation_number'],
                'guest_name' => $data['guest_name'],
                'guest_phone' => $data['guest_phone'] ?? null,
                'party_size' => $data['party_size'],
                'reserved_at' => $reservedAt,
                'status' => 'booked',
                'notes' => $data['notes'] ?? null,
            ]);

            $table->update(['status' => 'reserved']);
            $reservation->load(['diningTable', 'customer']);
            $this->audit->record('table_reservation.created', $reservation, after: $reservation->toArray(), request: $request);

            return $reservation;
        });
    }

    public function cancelReservation(Business $business, Branch $branch, TableReservation $reservation, Request $request): TableReservation
    {
        abort_unless($reservation->business_id === $business->id && $reservation->branch_id === $branch->id, 403);

        if ($reservation->status !== 'booked') {
            throw ValidationException::withMessages(['reservation' => ['Only booked reservations can be cancelled.']]);
        }

        return DB::transaction(function () use ($reservation, $request): TableReservation {
            $before = $reservation->toArray();
            $reservation->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);

            $this->releaseTableIfNoActiveReservation($reservation->diningTable);
            $this->audit->record('table_reservation.cancelled', $reservation, before: $before, after: $reservation->fresh()->toArray(), request: $request);

            return $reservation->fresh(['diningTable', 'customer']);
        });
    }

    public function seatReservation(Business $business, Branch $branch, TableReservation $reservation, Request $request): TableReservation
    {
        abort_unless($reservation->business_id === $business->id && $reservation->branch_id === $branch->id, 403);

        if ($reservation->status !== 'booked') {
            throw ValidationException::withMessages(['reservation' => ['Only booked reservations can be seated.']]);
        }

        return DB::transaction(function () use ($reservation, $request): TableReservation {
            $before = $reservation->toArray();
            $reservation->update([
                'status' => 'seated',
                'seated_at' => now(),
            ]);
            $reservation->diningTable->update(['status' => 'occupied']);

            $this->audit->record('table_reservation.seated', $reservation, before: $before, after: $reservation->fresh()->toArray(), request: $request);

            return $reservation->fresh(['diningTable', 'customer']);
        });
    }

    private function releaseTableIfNoActiveReservation(DiningTable $table): void
    {
        $hasActiveReservation = TableReservation::query()
            ->where('dining_table_id', $table->id)
            ->whereIn('status', ['booked', 'seated'])
            ->exists();

        if (! $hasActiveReservation && $table->status === 'reserved') {
            $table->update(['status' => 'available']);
        }
    }
}
