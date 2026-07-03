<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('delivery_fee_total', 18, 2)->default(0)->after('service_charge_total');
        });

        Schema::create('delivery_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('delivery_number');
            $table->string('status')->default('pending');
            $table->string('recipient_name');
            $table->string('recipient_phone')->nullable();
            $table->text('address');
            $table->text('notes')->nullable();
            $table->decimal('delivery_fee', 18, 2)->default(0);
            $table->string('courier_name')->nullable();
            $table->string('courier_phone')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'delivery_number']);
            $table->index(['business_id', 'branch_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_orders');

        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('delivery_fee_total');
        });
    }
};
