<?php

namespace App\Services\Tenancy;

use App\Models\Branch;
use App\Models\Business;
use Illuminate\Http\Request;

class ActiveContext
{
    public function __construct(private readonly Request $request)
    {
    }

    public function businessId(): ?int
    {
        $businessId = $this->request->session()->get('active_business_id');

        return $businessId !== null ? (int) $businessId : null;
    }

    public function outletId(): ?int
    {
        $outletId = $this->request->session()->get('active_outlet_id');

        return $outletId !== null ? (int) $outletId : null;
    }

    public function business(): ?Business
    {
        return $this->businessId() ? Business::query()->find($this->businessId()) : null;
    }

    public function outlet(): ?Branch
    {
        return $this->outletId() ? Branch::query()->find($this->outletId()) : null;
    }

    public function hasBusiness(): bool
    {
        return $this->businessId() !== null;
    }

    public function hasOutlet(): bool
    {
        return $this->outletId() !== null;
    }
}
