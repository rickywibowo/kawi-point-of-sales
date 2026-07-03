<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_drawer_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cashier_shift_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->uuid('uuid')->unique();
            $table->json('denomination_breakdown');
            $table->decimal('expected_cash', 18, 2);
            $table->decimal('counted_cash', 18, 2);
            $table->decimal('variance_amount', 18, 2);
            $table->string('status')->default('balanced');
            $table->text('variance_reason')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('audited_at');
            $table->timestamps();

            $table->unique('cashier_shift_id');
            $table->index(['business_id', 'branch_id', 'status', 'audited_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_drawer_audits');
    }
};
