<?php

namespace App\Services\Customers;

use App\Models\Business;
use App\Models\Customer;
use App\Models\CustomerLoyaltyTransaction;
use App\Models\Sale;
use App\Services\Audit\AuditLogger;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CustomerService
{
    public function __construct(private readonly AuditLogger $audit)
    {
    }

    public function list(Business $business, ?string $search = null): LengthAwarePaginator
    {
        return Customer::query()
            ->forBusiness($business->id)
            ->when($search, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->withCount('sales')
            ->withSum(['sales as lifetime_spend' => fn (Builder $query) => $query->where('status', 'completed')], 'grand_total')
            ->orderBy('name')
            ->paginate(20);
    }

    public function create(Business $business, array $data, Request $request): Customer
    {
        $this->assertUniqueContact($business->id, $data);

        return DB::transaction(function () use ($business, $data, $request): Customer {
            $customer = Customer::query()->create([
                'business_id' => $business->id,
                'name' => $data['name'],
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'address' => $data['address'] ?? null,
                'notes' => $data['notes'] ?? null,
                'receivable_balance' => $data['receivable_balance'] ?? 0,
                'loyalty_points' => $data['loyalty_points'] ?? 0,
                'is_active' => $data['is_active'] ?? true,
            ]);

            $this->audit->record('customer.created', $customer, after: $customer->toArray(), request: $request);

            return $customer;
        });
    }

    public function update(Customer $customer, array $data, Request $request): Customer
    {
        $this->assertUniqueContact($customer->business_id, $data, $customer->id);

        return DB::transaction(function () use ($customer, $data, $request): Customer {
            $before = $customer->toArray();
            $customer->fill($data);
            $customer->save();

            $this->audit->record('customer.updated', $customer, before: $before, after: $customer->fresh()->toArray(), request: $request);

            return $customer->fresh();
        });
    }

    public function profile(Customer $customer): array
    {
        $sales = Sale::query()
            ->where('customer_id', $customer->id)
            ->where('business_id', $customer->business_id);

        return [
            'customer' => $customer,
            'summary' => [
                'transaction_count' => (clone $sales)->where('status', 'completed')->count(),
                'lifetime_spend' => round((float) (clone $sales)->where('status', 'completed')->sum('grand_total'), 2),
                'average_order_value' => $this->averageOrderValue($sales),
                'last_purchase_at' => (clone $sales)->where('status', 'completed')->latest('sold_at')->value('sold_at'),
                'receivable_balance' => (float) $customer->receivable_balance,
                'loyalty_points' => $customer->loyalty_points,
            ],
            'recent_sales' => (clone $sales)
                ->with(['items.product', 'payments'])
                ->latest('sold_at')
                ->limit(10)
                ->get(),
            'loyalty_transactions' => $customer->loyaltyTransactions()
                ->latest()
                ->limit(20)
                ->get(),
        ];
    }

    public function adjustLoyalty(Customer $customer, array $data, Request $request): CustomerLoyaltyTransaction
    {
        return $this->recordLoyalty(
            $customer,
            $data['type'],
            (int) $data['points_delta'],
            $data['notes'] ?? null,
            null,
            null,
            $request,
        );
    }

    public function earnFromSale(Customer $customer, Sale $sale, Request $request): ?CustomerLoyaltyTransaction
    {
        $points = (int) floor((float) $sale->grand_total / 10000);

        if ($points <= 0) {
            return null;
        }

        if (CustomerLoyaltyTransaction::query()
            ->where('source_type', Sale::class)
            ->where('source_id', $sale->id)
            ->where('type', 'sale_earn')
            ->exists()) {
            return null;
        }

        return $this->recordLoyalty(
            $customer,
            'sale_earn',
            $points,
            'Earned from sale '.$sale->sale_number,
            Sale::class,
            $sale->id,
            $request,
        );
    }

    public function assertInBusiness(Business $business, int $customerId): Customer
    {
        $customer = Customer::query()
            ->forBusiness($business->id)
            ->whereKey($customerId)
            ->first();

        if (! $customer) {
            throw ValidationException::withMessages([
                'customer_id' => ['The selected customer is outside the active business.'],
            ]);
        }

        return $customer;
    }

    private function averageOrderValue(Builder $sales): float
    {
        $completed = (clone $sales)->where('status', 'completed');
        $count = (clone $completed)->count();

        if ($count === 0) {
            return 0;
        }

        return round((float) (clone $completed)->sum('grand_total') / $count, 2);
    }

    private function assertUniqueContact(int $businessId, array $data, ?int $ignoreId = null): void
    {
        foreach (['phone', 'email'] as $field) {
            if (empty($data[$field])) {
                continue;
            }

            $exists = Customer::query()
                ->forBusiness($businessId)
                ->where($field, $data[$field])
                ->when($ignoreId, fn (Builder $query) => $query->whereKeyNot($ignoreId))
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    $field => ["The {$field} is already used by another customer in this business."],
                ]);
            }
        }
    }

    private function recordLoyalty(
        Customer $customer,
        string $type,
        int $pointsDelta,
        ?string $notes,
        ?string $sourceType,
        ?int $sourceId,
        Request $request,
    ): CustomerLoyaltyTransaction {
        return DB::transaction(function () use ($customer, $type, $pointsDelta, $notes, $sourceType, $sourceId, $request): CustomerLoyaltyTransaction {
            $customer->refresh();
            $newBalance = (int) $customer->loyalty_points + $pointsDelta;

            if ($newBalance < 0) {
                throw ValidationException::withMessages(['points_delta' => ['Loyalty points cannot go below zero.']]);
            }

            $customer->update(['loyalty_points' => $newBalance]);

            $transaction = CustomerLoyaltyTransaction::query()->create([
                'business_id' => $customer->business_id,
                'customer_id' => $customer->id,
                'type' => $type,
                'points_delta' => $pointsDelta,
                'balance_after' => $newBalance,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'notes' => $notes,
                'created_by' => $request->user()?->id,
            ]);

            $this->audit->record('customer.loyalty_adjusted', $transaction, after: $transaction->toArray(), request: $request);

            return $transaction;
        });
    }
}
