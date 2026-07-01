<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unit_conversions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_unit_id')->constrained('unit_of_measures')->cascadeOnDelete();
            $table->foreignId('to_unit_id')->constrained('unit_of_measures')->cascadeOnDelete();
            $table->decimal('multiplier', 18, 6);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['business_id', 'from_unit_id', 'to_unit_id']);
        });

        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('code');
            $table->string('type')->default('branch');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['business_id', 'code']);
        });

        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->decimal('yield_quantity', 18, 6)->default(1);
            $table->foreignId('yield_unit_id')->nullable()->constrained('unit_of_measures')->nullOnDelete();
            $table->decimal('waste_percentage', 8, 4)->default(0);
            $table->decimal('computed_cost', 18, 2)->default(0);
            $table->unsignedInteger('version')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['business_id', 'product_id']);
        });

        Schema::create('recipe_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recipe_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ingredient_product_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('quantity', 18, 6);
            $table->foreignId('unit_of_measure_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('waste_percentage', 8, 4)->default(0);
            $table->decimal('unit_cost', 18, 2)->default(0);
            $table->decimal('line_cost', 18, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('stock_ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_of_measure_id')->nullable()->constrained()->nullOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('movement_type');
            $table->decimal('quantity_in', 18, 6)->default(0);
            $table->decimal('quantity_out', 18, 6)->default(0);
            $table->decimal('unit_cost', 18, 2)->default(0);
            $table->decimal('total_cost', 18, 2)->default(0);
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('reference_number')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('occurred_at');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['business_id', 'branch_id', 'warehouse_id']);
            $table->index(['product_id', 'occurred_at']);
            $table->index(['source_type', 'source_id']);
        });

        Schema::create('stock_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity_on_hand', 18, 6)->default(0);
            $table->decimal('average_cost', 18, 2)->default(0);
            $table->decimal('stock_value', 18, 2)->default(0);
            $table->timestamps();

            $table->unique(['warehouse_id', 'product_id']);
            $table->index(['business_id', 'branch_id']);
        });

        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('adjustment_number');
            $table->string('status')->default('posted');
            $table->text('notes')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'adjustment_number']);
        });

        Schema::create('stock_adjustment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_adjustment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_of_measure_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('quantity_delta', 18, 6);
            $table->decimal('unit_cost', 18, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_opnames', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('opname_number');
            $table->string('status')->default('draft');
            $table->timestamp('counted_at')->nullable();
            $table->foreignId('counted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['business_id', 'opname_number']);
        });

        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('to_branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('from_warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('to_warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('transfer_number');
            $table->string('status')->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'transfer_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfers');
        Schema::dropIfExists('stock_opnames');
        Schema::dropIfExists('stock_adjustment_items');
        Schema::dropIfExists('stock_adjustments');
        Schema::dropIfExists('stock_balances');
        Schema::dropIfExists('stock_ledgers');
        Schema::dropIfExists('recipe_items');
        Schema::dropIfExists('recipes');
        Schema::dropIfExists('warehouses');
        Schema::dropIfExists('unit_conversions');
    }
};
