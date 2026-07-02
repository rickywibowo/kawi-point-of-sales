<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operational_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('account_id')->constrained('accounts')->restrictOnDelete();
            $table->foreignId('cash_account_id')->constrained('accounts')->restrictOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('expense_number');
            $table->date('expense_date');
            $table->string('category')->nullable();
            $table->string('payee')->nullable();
            $table->text('description')->nullable();
            $table->decimal('amount', 18, 2);
            $table->string('payment_method')->default('cash');
            $table->string('reference_number')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['business_id', 'expense_number']);
            $table->index(['business_id', 'branch_id', 'expense_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operational_expenses');
    }
};
