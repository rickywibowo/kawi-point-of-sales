<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Business;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MeContextController extends Controller
{
    public function options(Request $request): JsonResponse
    {
        $user = $request->user();
        $businesses = $user->businesses()
            ->where('is_active', true)
            ->with(['branches' => fn ($query) => $query
                ->where('is_active', true)
                ->whereHas('users', fn ($query) => $query->whereKey($user->id))
                ->orderBy('name')])
            ->orderBy('name')
            ->get()
            ->filter(fn (Business $business) => $business->branches->isNotEmpty())
            ->values();

        return response()->json([
            'businesses' => $businesses->map(fn (Business $business): array => [
                'id' => $business->id,
                'uuid' => $business->uuid,
                'code' => $business->code,
                'name' => $business->name,
                'type' => $business->type,
                'outlets' => $business->branches->map(fn (Branch $outlet): array => $this->serializeOutlet($outlet))->values(),
            ]),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'business_id' => ['required', 'integer'],
            'outlet_id' => ['required', 'integer'],
        ]);

        $user = $request->user();
        $business = Business::query()
            ->whereKey($data['business_id'])
            ->where('is_active', true)
            ->first();
        $outlet = Branch::query()
            ->whereKey($data['outlet_id'])
            ->where('is_active', true)
            ->first();

        if (! $business || ! $outlet) {
            throw ValidationException::withMessages([
                'business_id' => ['The selected active context is invalid.'],
            ]);
        }

        if ((int) $outlet->business_id !== (int) $business->id) {
            throw ValidationException::withMessages([
                'outlet_id' => ['The selected outlet does not belong to the selected business.'],
            ]);
        }

        abort_unless($user->businesses()->whereKey($business->id)->exists(), 403, 'Business is not accessible.');
        abort_unless($user->outlets()->whereKey($outlet->id)->exists(), 403, 'Outlet is not accessible.');

        $request->session()->put('active_business_id', $business->id);
        $request->session()->put('active_outlet_id', $outlet->id);

        return response()->json($this->activeContextPayload($business, $outlet));
    }

    public function show(Request $request): JsonResponse
    {
        $businessId = $request->session()->get('active_business_id');
        $outletId = $request->session()->get('active_outlet_id');

        return response()->json($this->activeContextPayload(
            $businessId ? Business::query()->find($businessId) : null,
            $outletId ? Branch::query()->find($outletId) : null,
        ));
    }

    public function destroy(Request $request): JsonResponse
    {
        $request->session()->forget(['active_business_id', 'active_outlet_id']);

        return response()->json([
            'active_business' => null,
            'active_outlet' => null,
        ]);
    }

    private function activeContextPayload(?Business $business, ?Branch $outlet): array
    {
        return [
            'active_business' => $business ? [
                'id' => $business->id,
                'uuid' => $business->uuid,
                'code' => $business->code,
                'name' => $business->name,
                'type' => $business->type,
            ] : null,
            'active_outlet' => $outlet ? $this->serializeOutlet($outlet) : null,
        ];
    }

    private function serializeOutlet(Branch $outlet): array
    {
        return [
            'id' => $outlet->id,
            'uuid' => $outlet->uuid,
            'business_id' => $outlet->business_id,
            'code' => $outlet->code,
            'name' => $outlet->name,
        ];
    }
}
