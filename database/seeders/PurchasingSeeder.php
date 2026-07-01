<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PurchasingSeeder extends Seeder
{
    public function run(): void
    {
        $business = Business::query()->where('name', 'KAWI Demo Business')->firstOrFail();
        $supplier = Supplier::query()->where('business_id', $business->id)->firstOrFail();
        $warehouse = Warehouse::query()->where('business_id', $business->id)->firstOrFail();
        $product = Product::query()->where('business_id', $business->id)->where('sku', 'KAWI-COFFEE-001')->firstOrFail();

        $po = PurchaseOrder::query()->firstOrCreate(
            ['business_id' => $business->id, 'po_number' => 'PO-SEED-001'],
            [
                'branch_id' => $warehouse->branch_id,
                'supplier_id' => $supplier->id,
                'warehouse_id' => $warehouse->id,
                'uuid' => (string) Str::uuid(),
                'status' => 'approved',
                'order_date' => now()->toDateString(),
                'subtotal' => 90000,
                'tax_total' => 9900,
                'grand_total' => 99900,
            ],
        );

        $po->items()->firstOrCreate(
            ['product_id' => $product->id],
            [
                'unit_of_measure_id' => $product->unit_of_measure_id,
                'quantity_ordered' => 10,
                'unit_cost' => 9000,
                'tax_rate' => 11,
                'tax_total' => 9900,
                'line_total' => 99900,
            ],
        );
    }
}
