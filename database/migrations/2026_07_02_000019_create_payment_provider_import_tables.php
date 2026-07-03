<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_provider_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('payment_settlement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('imported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('import_number');
            $table->string('provider');
            $table->string('method');
            $table->date('settlement_date')->nullable();
            $table->unsignedInteger('row_count')->default(0);
            $table->unsignedInteger('matched_count')->default(0);
            $table->unsignedInteger('unmatched_count')->default(0);
            $table->decimal('gross_amount', 18, 2)->default(0);
            $table->decimal('fee_amount', 18, 2)->default(0);
            $table->decimal('received_amount', 18, 2)->default(0);
            $table->decimal('variance_to_settlement', 18, 2)->default(0);
            $table->string('status')->default('imported');
            $table->text('notes')->nullable();
            $table->timestamp('imported_at');
            $table->timestamps();

            $table->unique(['business_id', 'import_number']);
            $table->index(['business_id', 'branch_id', 'provider', 'method']);
        });

        Schema::create('payment_provider_import_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_provider_import_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sale_payment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reference');
            $table->decimal('gross_amount', 18, 2);
            $table->decimal('fee_amount', 18, 2)->default(0);
            $table->decimal('received_amount', 18, 2);
            $table->timestamp('settled_at')->nullable();
            $table->string('status')->default('unmatched');
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->index(['reference', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_provider_import_rows');
        Schema::dropIfExists('payment_provider_imports');
    }
};
