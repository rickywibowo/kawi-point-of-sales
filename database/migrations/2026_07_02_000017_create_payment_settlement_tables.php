<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('settlement_number');
            $table->string('method');
            $table->date('date_from');
            $table->date('date_to');
            $table->decimal('expected_amount', 18, 2)->default(0);
            $table->decimal('reported_amount', 18, 2)->default(0);
            $table->decimal('variance_amount', 18, 2)->default(0);
            $table->string('status')->default('draft');
            $table->text('notes')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['business_id', 'settlement_number']);
            $table->index(['business_id', 'branch_id', 'method', 'date_from', 'date_to']);
        });

        Schema::create('payment_settlement_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_settlement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sale_payment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 18, 2);
            $table->string('reference')->nullable();
            $table->timestamps();

            $table->unique('sale_payment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_settlement_items');
        Schema::dropIfExists('payment_settlements');
    }
};
