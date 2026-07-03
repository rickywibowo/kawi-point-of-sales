<?php

namespace App\Services\Pos;

use App\Models\Business;
use App\Models\Branch;
use App\Models\KitchenStation;
use App\Models\KitchenTicket;
use App\Models\KitchenTicketItem;
use App\Models\Sale;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class KitchenService
{
    public function __construct(private readonly AuditLogger $audit)
    {
    }

    public function createTicketForSale(Sale $sale, ?Request $request = null): KitchenTicket
    {
        if (KitchenTicket::query()->where('sale_id', $sale->id)->exists()) {
            return KitchenTicket::query()->where('sale_id', $sale->id)->with('items')->firstOrFail();
        }

        return DB::transaction(function () use ($sale, $request): KitchenTicket {
            $sale->loadMissing(['items.product.kitchenStation', 'diningTable']);
            $ticket = KitchenTicket::query()->create([
                'business_id' => $sale->business_id,
                'branch_id' => $sale->branch_id,
                'sale_id' => $sale->id,
                'dining_table_id' => $sale->dining_table_id,
                'ticket_number' => 'KOT-'.$sale->sale_number,
                'status' => 'open',
                'opened_at' => now(),
            ]);

            foreach ($sale->items as $item) {
                $station = $item->product?->kitchenStation;
                $ticket->items()->create([
                    'sale_item_id' => $item->id,
                    'kitchen_station_id' => $station?->id,
                    'product_name' => $item->product_name,
                    'station_name' => $station?->name ?? 'General',
                    'course' => 'main',
                    'station_sequence' => $station?->sort_order ?? 999,
                    'quantity' => $item->quantity,
                    'notes' => $item->notes,
                    'status' => 'pending',
                ]);
            }

            $ticket->load(['items.kitchenStation', 'sale', 'diningTable']);
            $this->audit->record('kitchen_ticket.created', $ticket, after: $ticket->toArray(), request: $request);

            return $ticket;
        });
    }

    public function updateTicketStatus(Business $business, ?int $branchId, KitchenTicket $ticket, string $status, Request $request): KitchenTicket
    {
        abort_unless($ticket->business_id === $business->id && ($branchId === null || $ticket->branch_id === $branchId), 403);

        if (! in_array($status, ['open', 'preparing', 'ready', 'served', 'cancelled'], true)) {
            throw ValidationException::withMessages(['status' => ['Invalid kitchen ticket status.']]);
        }

        $before = $ticket->toArray();
        $ticket->update($this->statusPayload($status));

        if ($status === 'served') {
            $ticket->items()->where('status', '!=', 'cancelled')->update([
                'status' => 'served',
                'completed_at' => now(),
            ]);
        }

        $this->audit->record('kitchen_ticket.status_updated', $ticket, before: $before, after: $ticket->fresh()->toArray(), request: $request);

        return $ticket->fresh(['items', 'sale', 'diningTable']);
    }

    public function updateItemStatus(Business $business, ?int $branchId, KitchenTicketItem $item, string $status, Request $request): KitchenTicketItem
    {
        $item->loadMissing('ticket');
        abort_unless($item->ticket->business_id === $business->id && ($branchId === null || $item->ticket->branch_id === $branchId), 403);

        if (! in_array($status, ['preparing', 'ready', 'served', 'cancelled'], true)) {
            throw ValidationException::withMessages(['status' => ['Invalid kitchen item status.']]);
        }

        $before = $item->toArray();
        $item->update($this->statusPayload($status));
        $this->syncTicketStatus($item->ticket->fresh('items'));
        $this->audit->record('kitchen_ticket_item.status_updated', $item, before: $before, after: $item->fresh()->toArray(), request: $request);

        return $item->fresh(['ticket']);
    }

    public function createStation(Business $business, Branch $branch, array $data, Request $request): KitchenStation
    {
        $exists = KitchenStation::query()
            ->where('business_id', $business->id)
            ->where('branch_id', $branch->id)
            ->where('code', $data['code'])
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages(['code' => ['Kitchen station code already exists in this branch.']]);
        }

        $station = KitchenStation::query()->create([
            'business_id' => $business->id,
            'branch_id' => $branch->id,
            'code' => $data['code'],
            'name' => $data['name'],
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => $data['is_active'] ?? true,
        ]);

        $this->audit->record('kitchen_station.created', $station, after: $station->toArray(), request: $request);

        return $station;
    }

    public function slipPayload(Business $business, ?int $branchId, KitchenTicket $ticket): array
    {
        abort_unless($ticket->business_id === $business->id && ($branchId === null || $ticket->branch_id === $branchId), 403);

        $ticket->load(['items.kitchenStation', 'sale', 'diningTable']);

        return [
            'ticket' => [
                'number' => $ticket->ticket_number,
                'status' => $ticket->status,
                'opened_at' => $ticket->opened_at?->toIso8601String(),
                'table' => $ticket->diningTable?->code ?? ucfirst((string) ($ticket->sale?->type ?? 'takeaway')),
            ],
            'stations' => $ticket->items
                ->sortBy(['station_sequence', 'product_name'])
                ->groupBy(fn (KitchenTicketItem $item): string => $item->station_name ?? 'General')
                ->map(fn ($items, string $stationName): array => [
                    'station' => $stationName,
                    'items' => $items->map(fn (KitchenTicketItem $item): array => [
                        'product_name' => $item->product_name,
                        'quantity' => (float) $item->quantity,
                        'course' => $item->course,
                        'notes' => $item->notes,
                        'status' => $item->status,
                    ])->values()->all(),
                ])
                ->values()
                ->all(),
        ];
    }

    private function syncTicketStatus(KitchenTicket $ticket): void
    {
        $items = $ticket->items;

        if ($items->where('status', 'preparing')->isNotEmpty()) {
            $ticket->update($this->statusPayload('preparing'));

            return;
        }

        if ($items->whereNotIn('status', ['ready', 'served', 'cancelled'])->isEmpty()) {
            $ticket->update($this->statusPayload('ready'));
        }
    }

    private function statusPayload(string $status): array
    {
        return [
            'status' => $status,
            'started_at' => $status === 'preparing' ? now() : null,
            'completed_at' => in_array($status, ['ready', 'served', 'cancelled'], true) ? now() : null,
        ];
    }
}
