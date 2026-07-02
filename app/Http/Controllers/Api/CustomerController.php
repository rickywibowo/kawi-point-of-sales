<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customers\StoreCustomerRequest;
use App\Http\Requests\Customers\StoreLoyaltyTransactionRequest;
use App\Http\Requests\Customers\UpdateCustomerRequest;
use App\Services\Customers\CustomerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request, CustomerService $customers): JsonResponse
    {
        return response()->json([
            'customers' => $customers->list(
                $request->attributes->get('business'),
                $request->query('search'),
            ),
        ]);
    }

    public function store(StoreCustomerRequest $request, CustomerService $customers): JsonResponse
    {
        $customer = $customers->create(
            $request->attributes->get('business'),
            $request->validated(),
            $request,
        );

        return response()->json(['customer' => $customer], 201);
    }

    public function show(Request $request, CustomerService $customers, int $customer): JsonResponse
    {
        $customer = $customers->assertInBusiness($request->attributes->get('business'), $customer);

        return response()->json($customers->profile($customer));
    }

    public function update(UpdateCustomerRequest $request, CustomerService $customers, int $customer): JsonResponse
    {
        $customer = $customers->assertInBusiness($request->attributes->get('business'), $customer);

        return response()->json([
            'customer' => $customers->update($customer, $request->validated(), $request),
        ]);
    }

    public function loyalty(StoreLoyaltyTransactionRequest $request, CustomerService $customers, int $customer): JsonResponse
    {
        $customer = $customers->assertInBusiness($request->attributes->get('business'), $customer);

        return response()->json([
            'loyalty_transaction' => $customers->adjustLoyalty($customer, $request->validated(), $request),
            'customer' => $customer->fresh(),
        ], 201);
    }
}
