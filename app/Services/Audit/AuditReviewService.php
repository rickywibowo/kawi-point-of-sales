<?php

namespace App\Services\Audit;

use App\Models\AuditLog;
use App\Models\Business;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AuditReviewService
{
    public function logs(Business $business, ?int $branchId = null, array $filters = []): LengthAwarePaginator
    {
        return $this->baseQuery($business->id, $branchId, $filters)
            ->with(['user', 'branch'])
            ->latest()
            ->paginate((int) ($filters['per_page'] ?? 25));
    }

    public function summary(Business $business, ?int $branchId = null, array $filters = []): array
    {
        $query = $this->baseQuery($business->id, $branchId, $filters);

        return [
            'total_events' => (clone $query)->count(),
            'unique_users' => (clone $query)->whereNotNull('user_id')->distinct('user_id')->count('user_id'),
            'actions' => (clone $query)
                ->selectRaw('action, count(*) as total')
                ->groupBy('action')
                ->orderByDesc('total')
                ->limit(20)
                ->get(),
            'recent_security_events' => $this->securityEvents($business->id, $branchId),
        ];
    }

    private function baseQuery(int $businessId, ?int $branchId, array $filters): Builder
    {
        return AuditLog::query()
            ->where('business_id', $businessId)
            ->when($branchId, fn (Builder $query) => $query->where(function (Builder $query) use ($branchId): void {
                $query->whereNull('branch_id')->orWhere('branch_id', $branchId);
            }))
            ->when($filters['action'] ?? null, fn (Builder $query, string $action) => $query->where('action', $action))
            ->when($filters['entity_type'] ?? null, fn (Builder $query, string $entityType) => $query->where('entity_type', $entityType))
            ->when($filters['user_id'] ?? null, fn (Builder $query, int|string $userId) => $query->where('user_id', $userId))
            ->when($filters['date_from'] ?? null, fn (Builder $query, string $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn (Builder $query, string $date) => $query->whereDate('created_at', '<=', $date));
    }

    private function securityEvents(int $businessId, ?int $branchId): Collection
    {
        return AuditLog::query()
            ->where('business_id', $businessId)
            ->when($branchId, fn (Builder $query) => $query->where(function (Builder $query) use ($branchId): void {
                $query->whereNull('branch_id')->orWhere('branch_id', $branchId);
            }))
            ->whereIn('action', ['user.invited', 'role.assigned', 'sale.voided', 'sale.refunded', 'journal.posted'])
            ->with(['user', 'branch'])
            ->latest()
            ->limit(10)
            ->get();
    }
}
