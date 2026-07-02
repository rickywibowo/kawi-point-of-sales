<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_return_id')->constrained()->cascadeOnDelete();
            $table->foreignId('goods_receipt_item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('unit_of_measure_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('quantity_returned', 18, 6);
            $table->decimal('unit_cost', 18, 2);
            $table->decimal('tax_rate', 8, 4)->default(0);
            $table->decimal('tax_total', 18, 2)->default(0);
            $table->decimal('line_total', 18, 2);
            $table->text('reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_return_items');
    }
};
