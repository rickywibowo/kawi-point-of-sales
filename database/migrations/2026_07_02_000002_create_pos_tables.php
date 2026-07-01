<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cashier_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('shift_number');
            $table->decimal('opening_cash', 18, 2)->default(0);
            $table->decimal('expected_cash', 18, 2)->default(0);
            $table->decimal('actual_cash', 18, 2)->nullable();
            $table->decimal('cash_difference', 18, 2)->nullable();
            $table->string('status')->default('open');
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'shift_number']);
            $table->index(['business_id', 'branch_id', 'status']);
        });

        Schema::create('cash_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cashier_shift_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->decimal('amount', 18, 2);
            $table->string('reason')->nullable();
            $table->timestamps();
        });

        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cashier_shift_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cashier_id')->nullable()->constrained('users')->nullOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('sale_number');
            $table->string('idempotency_key')->nullable();
            $table->string('type')->default('takeaway');
            $table->string('status')->default('completed');
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('discount_total', 18, 2)->default(0);
            $table->decimal('tax_total', 18, 2)->default(0);
            $table->decimal('service_charge_total', 18, 2)->default(0);
            $table->decimal('grand_total', 18, 2)->default(0);
            $table->decimal('paid_total', 18, 2)->default(0);
            $table->decimal('change_total', 18, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('sold_at')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('refunded_at')->nullable();
            $table->foreignId('refunded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['business_id', 'sale_number']);
            $table->unique(['business_id', 'idempotency_key']);
            $table->index(['business_id', 'branch_id', 'sold_at']);
            $table->index(['status', 'type']);
        });

        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name');
            $table->decimal('quantity', 18, 6);
            $table->decimal('unit_price', 18, 2);
            $table->decimal('discount_total', 18, 2)->default(0);
            $table->decimal('tax_total', 18, 2)->default(0);
            $table->decimal('line_total', 18, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('sale_item_modifiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('modifier_id')->nullable()->constrained()->nullOnDelete();
            $table->string('modifier_name');
            $table->decimal('price_delta', 18, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('sale_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('method');
            $table->decimal('amount', 18, 2);
            $table->string('reference')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('held_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cashier_shift_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cashier_id')->nullable()->constrained('users')->nullOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('hold_number');
            $table->json('payload');
            $table->timestamp('held_at');
            $table->timestamp('resumed_at')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'hold_number']);
            $table->index(['business_id', 'branch_id', 'held_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('held_transactions');
        Schema::dropIfExists('sale_payments');
        Schema::dropIfExists('sale_item_modifiers');
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
        Schema::dropIfExists('cash_movements');
        Schema::dropIfExists('cashier_shifts');
    }
};
