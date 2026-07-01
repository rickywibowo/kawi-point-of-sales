<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offline_sync_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('batch_key');
            $table->string('status')->default('processed');
            $table->unsignedInteger('received_count')->default(0);
            $table->unsignedInteger('synced_count')->default(0);
            $table->unsignedInteger('conflict_count')->default(0);
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'batch_key']);
            $table->index(['business_id', 'branch_id', 'status']);
        });

        Schema::create('offline_sync_conflicts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('offline_sync_batch_id')->constrained()->cascadeOnDelete();
            $table->string('client_uuid');
            $table->string('idempotency_key')->nullable();
            $table->string('reason');
            $table->json('payload');
            $table->string('status')->default('open');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'branch_id', 'status']);
            $table->index(['idempotency_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offline_sync_conflicts');
        Schema::dropIfExists('offline_sync_batches');
    }
};
