<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dining_tables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('code');
            $table->string('name');
            $table->unsignedInteger('capacity')->default(2);
            $table->string('section')->nullable();
            $table->string('status')->default('available');
            $table->timestamps();

            $table->unique(['branch_id', 'code']);
            $table->index(['business_id', 'branch_id', 'status']);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('dining_table_id')->nullable()->after('customer_id')->constrained('dining_tables')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('dining_table_id');
        });

        Schema::dropIfExists('dining_tables');
    }
};
