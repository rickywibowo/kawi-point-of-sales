<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\UpdateKitchenStatusRequest;
use App\Models\KitchenTicket;
use App\Models\KitchenTicketItem;
use App\Services\Pos\KitchenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KitchenTicketController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $business = $request->attributes->get('business');
        $branch = $request->attributes->get('branch');

        return response()->json([
            'kitchen_tickets' => KitchenTicket::query()
                ->forTenant($business->id, $branch?->id)
                ->with(['items', 'sale', 'diningTable'])
                ->whereIn('status', ['open', 'preparing', 'ready'])
                ->latest('opened_at')
                ->get(),
        ]);
    }

    public function updateStatus(UpdateKitchenStatusRequest $request, KitchenTicket $ticket, KitchenService $kitchen): JsonResponse
    {
        $ticket = $kitchen->updateTicketStatus(
            $request->attributes->get('business'),
            $request->attributes->get('branch')?->id,
            $ticket,
            $request->validated('status'),
            $request,
        );

        return response()->json(['kitchen_ticket' => $ticket]);
    }

    public function updateItemStatus(UpdateKitchenStatusRequest $request, KitchenTicketItem $item, KitchenService $kitchen): JsonResponse
    {
        $item = $kitchen->updateItemStatus(
            $request->attributes->get('business'),
            $request->attributes->get('branch')?->id,
            $item,
            $request->validated('status'),
            $request,
        );

        return response()->json(['kitchen_ticket_item' => $item]);
    }
}
