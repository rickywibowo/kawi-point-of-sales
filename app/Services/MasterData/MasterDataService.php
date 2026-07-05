<?php

namespace App\Services\MasterData;

use App\Models\BranchProductPrice;
use App\Models\Business;
use App\Models\Category;
use App\Models\Product;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MasterDataService
{
    public function __construct(private readonly AuditLogger $audit)
    {
    }

    public function createCategory(Business $business, array $data, Request $request): Category
    {
        if (! empty($data['parent_id'])) {
            $parentExists = Category::query()
                ->forBusiness($business->id)
                ->whereKey($data['parent_id'])
                ->exists();

            if (! $parentExists) {
                throw ValidationException::withMessages([
                    'parent_id' => ['The selected parent category is outside the active business.'],
                ]);
            }
        }

        return DB::transaction(function () use ($business, $data, $request): Category {
            $category = Category::query()->create([
                'business_id' => $business->id,
                'parent_id' => $data['parent_id'] ?? null,
                'name' => $data['name'],
                'slug' => $data['slug'] ?? Str::slug($data['name']),
                'sort_order' => $data['sort_order'] ?? 0,
                'is_active' => $data['is_active'] ?? true,
            ]);

            $this->audit->record('category.created', $category, after: $category->toArray(), request: $request);

            return $category;
        });
    }

    public function createProduct(Business $business, array $data, Request $request): Product
    {
        $this->assertBusinessEntity(Category::class, $business->id, $data['category_id'] ?? null, 'category_id');
        $this->assertBusinessEntity(\App\Models\UnitOfMeasure::class, $business->id, $data['unit_of_measure_id'] ?? null, 'unit_of_measure_id');
        $this->assertBusinessEntity(\App\Models\Tax::class, $business->id, $data['tax_id'] ?? null, 'tax_id');
        $this->assertKitchenStation($business->id, $data['kitchen_station_id'] ?? null);

        foreach ($data['branch_prices'] ?? [] as $index => $branchPrice) {
            $branchExists = \App\Models\Branch::query()
                ->where('business_id', $business->id)
                ->whereKey($branchPrice['branch_id'])
                ->exists();

            if (! $branchExists) {
                throw ValidationException::withMessages([
                    "branch_prices.$index.branch_id" => ['The selected branch is outside the active business.'],
                ]);
            }
        }

        return DB::transaction(function () use ($business, $data, $request): Product {
            $product = Product::query()->create([
                'business_id' => $business->id,
                'category_id' => $data['category_id'] ?? null,
                'unit_of_measure_id' => $data['unit_of_measure_id'] ?? null,
                'tax_id' => $data['tax_id'] ?? null,
                'kitchen_station_id' => $data['kitchen_station_id'] ?? null,
                'uuid' => (string) Str::uuid(),
                'name' => $data['name'],
                'type' => $data['type'],
                'sku' => $data['sku'] ?? null,
                'barcode' => $data['barcode'] ?? null,
                'base_price' => $data['base_price'] ?? 0,
                'cost_price' => $data['cost_price'] ?? 0,
                'track_stock' => $data['track_stock'] ?? true,
                'is_active' => $data['is_active'] ?? true,
            ]);

            foreach ($data['branch_prices'] ?? [] as $branchPrice) {
                BranchProductPrice::query()->create([
                    'business_id' => $business->id,
                    'branch_id' => $branchPrice['branch_id'],
                    'product_id' => $product->id,
                    'price' => $branchPrice['price'],
                    'is_active' => $branchPrice['is_active'] ?? true,
                ]);
            }

            $product->load(['category', 'unitOfMeasure', 'tax', 'kitchenStation', 'branchPrices']);
            $this->audit->record('product.created', $product, after: $product->toArray(), request: $request);

            return $product;
        });
    }

    public function deleteCategory(Business $business, int $categoryId, Request $request): void
    {
        $category = Category::query()
            ->forBusiness($business->id)
            ->whereKey($categoryId)
            ->first();

        if (! $category) {
            throw ValidationException::withMessages([
                'category_id' => ['The selected category is outside the active business.'],
            ]);
        }

        if ($category->children()->exists()) {
            throw ValidationException::withMessages([
                'category_id' => ['Category with child categories cannot be deleted.'],
            ]);
        }

        if ($category->products()->exists()) {
            throw ValidationException::withMessages([
                'category_id' => ['Category with products cannot be deleted.'],
            ]);
        }

        DB::transaction(function () use ($category, $request): void {
            $before = $category->toArray();
            $category->delete();

            $this->audit->record('category.deleted', $category, before: $before, request: $request);
        });
    }

    private function assertBusinessEntity(string $model, int $businessId, ?int $id, string $field): void
    {
        if ($id === null) {
            return;
        }

        $exists = $model::query()->forBusiness($businessId)->whereKey($id)->exists();

        if (! $exists) {
            throw ValidationException::withMessages([
                $field => ['The selected value is outside the active business.'],
            ]);
        }
    }

    private function assertKitchenStation(int $businessId, ?int $id): void
    {
        if ($id === null) {
            return;
        }

        $exists = \App\Models\KitchenStation::query()
            ->where('business_id', $businessId)
            ->whereKey($id)
            ->exists();

        if (! $exists) {
            throw ValidationException::withMessages([
                'kitchen_station_id' => ['The selected kitchen station is outside the active business.'],
            ]);
        }
    }
}
