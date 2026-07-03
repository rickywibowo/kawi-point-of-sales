<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('table_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dining_table_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('reservation_number');
            $table->string('guest_name');
            $table->string('guest_phone')->nullable();
            $table->unsignedInteger('party_size')->default(1);
            $table->timestamp('reserved_at');
            $table->string('status')->default('booked');
            $table->text('notes')->nullable();
            $table->timestamp('seated_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'reservation_number']);
            $table->index(['business_id', 'branch_id', 'reserved_at']);
            $table->index(['dining_table_id', 'status', 'reserved_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('table_reservations');
    }
};
