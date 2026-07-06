<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\BranchProductPrice;
use App\Models\Business;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Modifier;
use App\Models\ModifierGroup;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Tax;
use App\Models\UnitOfMeasure;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $business = Business::query()->where('name', 'KAWI Demo Business')->firstOrFail();
        $branch = Branch::query()->where('business_id', $business->id)->where('code', 'MAIN')->firstOrFail();

        $pcs = UnitOfMeasure::query()->firstOrCreate(
            ['business_id' => $business->id, 'code' => 'PCS'],
            ['name' => 'Pieces', 'type' => 'unit', 'base_multiplier' => 1],
        );

        UnitOfMeasure::query()->firstOrCreate(
            ['business_id' => $business->id, 'code' => 'GRAM'],
            ['name' => 'Gram', 'type' => 'weight', 'base_multiplier' => 1],
        );

        $tax = Tax::query()->firstOrCreate(
            ['business_id' => $business->id, 'code' => 'PPN11'],
            ['name' => 'PPN 11%', 'rate' => 11, 'is_inclusive' => false],
        );

        $food = Category::query()->firstOrCreate(
            ['business_id' => $business->id, 'branch_id' => $branch->id, 'slug' => 'makanan'],
            ['name' => 'Makanan', 'sort_order' => 1],
        );

        $beverage = Category::query()->firstOrCreate(
            ['business_id' => $business->id, 'branch_id' => $branch->id, 'slug' => 'minuman'],
            ['name' => 'Minuman', 'sort_order' => 2],
        );

        Category::query()->firstOrCreate(
            ['business_id' => $business->id, 'branch_id' => $branch->id, 'slug' => 'kopi'],
            ['parent_id' => $beverage->id, 'name' => 'Kopi', 'sort_order' => 1],
        );

        Supplier::query()->firstOrCreate(
            ['business_id' => $business->id, 'name' => 'Supplier Bahan Baku KAWI'],
            ['phone' => '081234567890', 'address' => 'Makassar'],
        );

        Customer::query()->firstOrCreate(
            ['business_id' => $business->id, 'phone' => '080000000001'],
            ['name' => 'Walk-in Customer'],
        );

        $riceBowl = Product::query()->firstOrCreate(
            ['business_id' => $business->id, 'branch_id' => $branch->id, 'sku' => 'KAWI-RICE-001'],
            [
                'uuid' => (string) Str::uuid(),
                'category_id' => $food->id,
                'unit_of_measure_id' => $pcs->id,
                'tax_id' => $tax->id,
                'name' => 'KAWI Rice Bowl',
                'type' => 'food',
                'barcode' => '899000000001',
                'base_price' => 35000,
                'cost_price' => 18000,
                'track_stock' => true,
            ],
        );

        $icedCoffee = Product::query()->firstOrCreate(
            ['business_id' => $business->id, 'branch_id' => $branch->id, 'sku' => 'KAWI-COFFEE-001'],
            [
                'uuid' => (string) Str::uuid(),
                'category_id' => $beverage->id,
                'unit_of_measure_id' => $pcs->id,
                'tax_id' => $tax->id,
                'name' => 'KAWI Iced Coffee',
                'type' => 'beverage',
                'barcode' => '899000000002',
                'base_price' => 25000,
                'cost_price' => 9000,
                'track_stock' => true,
            ],
        );

        foreach ([$riceBowl, $icedCoffee] as $product) {
            BranchProductPrice::query()->firstOrCreate(
                ['branch_id' => $branch->id, 'product_id' => $product->id],
                ['business_id' => $business->id, 'price' => $product->base_price],
            );
        }

        $toppings = ModifierGroup::query()->firstOrCreate(
            ['business_id' => $business->id, 'name' => 'Topping'],
            ['min_select' => 0, 'max_select' => 3, 'is_required' => false],
        );

        Modifier::query()->firstOrCreate(
            ['business_id' => $business->id, 'modifier_group_id' => $toppings->id, 'name' => 'Extra Sambal'],
            ['price_delta' => 3000, 'cost_delta' => 500],
        );

        Modifier::query()->firstOrCreate(
            ['business_id' => $business->id, 'modifier_group_id' => $toppings->id, 'name' => 'Extra Shot'],
            ['price_delta' => 6000, 'cost_delta' => 2000],
        );

        $riceBowl->modifierGroups()->syncWithoutDetaching([$toppings->id]);
        $icedCoffee->modifierGroups()->syncWithoutDetaching([$toppings->id]);
    }
}
