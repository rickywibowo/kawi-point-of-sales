<?php

namespace App\Services\Tenancy;

use App\Models\User;
use App\Support\UserContextOptions;
use Illuminate\Http\Request;

class FilamentActiveContextManager
{
    public function __construct(private readonly Request $request) {}

    public function ensureValidOrAutoSelect(User $user): bool
    {
        $contexts = UserContextOptions::forUser($user);
        $businessId = $this->businessId();
        $outletId = $this->outletId();

        if ($businessId && ! $this->findBusiness($contexts, $businessId)) {
            $this->clear();
            $businessId = null;
            $outletId = null;
        }

        if (! $businessId) {
            if (count($contexts) !== 1) {
                return false;
            }

            $businessId = (int) $contexts[0]['id'];
            $this->request->session()->put('active_business_id', $businessId);
            $this->request->session()->forget('active_outlet_id');
            $outletId = null;
        }

        $business = $this->findBusiness($contexts, $businessId);

        if (! $business) {
            $this->clear();

            return false;
        }

        if ($outletId && ! $this->findOutlet($business, $outletId)) {
            $this->request->session()->forget('active_outlet_id');
            $outletId = null;
        }

        if (! $outletId) {
            if (count($business['branches']) !== 1) {
                return false;
            }

            $outletId = (int) $business['branches'][0]['id'];
            $this->request->session()->put('active_outlet_id', $outletId);
        }

        return (bool) $this->findOutlet($business, $outletId);
    }

    public function switchBusiness(User $user, int $businessId): void
    {
        $contexts = UserContextOptions::forUser($user);
        $business = $this->findBusiness($contexts, $businessId);

        abort_unless($business, 403, 'Selected business is not accessible.');

        $this->request->session()->put('active_business_id', $businessId);
        $this->request->session()->forget('active_outlet_id');

        if (count($business['branches']) === 1) {
            $this->request->session()->put('active_outlet_id', (int) $business['branches'][0]['id']);
        }
    }

    public function switchOutlet(User $user, int $businessId, int $outletId): void
    {
        $contexts = UserContextOptions::forUser($user);
        $business = $this->findBusiness($contexts, $businessId);

        abort_unless($business, 403, 'Selected business is not accessible.');
        abort_unless($this->findOutlet($business, $outletId), 403, 'Selected outlet is not accessible.');

        $this->request->session()->put('active_business_id', $businessId);
        $this->request->session()->put('active_outlet_id', $outletId);
    }

    public function clear(): void
    {
        $this->request->session()->forget(['active_business_id', 'active_outlet_id']);
    }

    private function businessId(): ?int
    {
        $businessId = $this->request->session()->get('active_business_id');

        return $businessId !== null ? (int) $businessId : null;
    }

    private function outletId(): ?int
    {
        $outletId = $this->request->session()->get('active_outlet_id');

        return $outletId !== null ? (int) $outletId : null;
    }

    private function findBusiness(array $contexts, int $businessId): ?array
    {
        foreach ($contexts as $business) {
            if ((int) $business['id'] === $businessId) {
                return $business;
            }
        }

        return null;
    }

    private function findOutlet(array $business, int $outletId): ?array
    {
        foreach ($business['branches'] as $outlet) {
            if ((int) $outlet['id'] === $outletId) {
                return $outlet;
            }
        }

        return null;
    }
}
