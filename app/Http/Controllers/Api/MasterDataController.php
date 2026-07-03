<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Customer;
use App\Models\KitchenStation;
use App\Models\ModifierGroup;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Tax;
use App\Models\UnitOfMeasure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MasterDataController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $business = $request->attributes->get('business');

        return response()->json([
            'units' => UnitOfMeasure::query()->forBusiness($business->id)->orderBy('name')->get(),
            'taxes' => Tax::query()->forBusiness($business->id)->orderBy('name')->get(),
            'categories' => Category::query()->forBusiness($business->id)->with('children')->whereNull('parent_id')->orderBy('sort_order')->orderBy('name')->get(),
            'suppliers' => Supplier::query()->forBusiness($business->id)->orderBy('name')->get(),
            'customers' => Customer::query()->forBusiness($business->id)->orderBy('name')->get(),
            'modifier_groups' => ModifierGroup::query()->forBusiness($business->id)->with('modifiers')->orderBy('name')->get(),
            'kitchen_stations' => KitchenStation::query()->where('business_id', $business->id)->orderBy('sort_order')->orderBy('name')->get(),
            'products' => Product::query()
                ->forBusiness($business->id)
                ->with(['category', 'unitOfMeasure', 'tax', 'kitchenStation', 'variants', 'branchPrices', 'modifierGroups.modifiers'])
                ->orderBy('name')
                ->get(),
        ]);
    }
}
