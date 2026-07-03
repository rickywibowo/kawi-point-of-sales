<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('code');
            $table->string('name');
            $table->string('type');
            $table->decimal('value', 18, 2);
            $table->decimal('minimum_subtotal', 18, 2)->default(0);
            $table->decimal('maximum_discount', 18, 2)->nullable();
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('usage_count')->default(0);
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['business_id', 'code']);
            $table->index(['business_id', 'is_active']);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('promotion_id')->nullable()->after('dining_table_id')->constrained('promotions')->nullOnDelete();
            $table->string('promotion_code')->nullable()->after('promotion_id');
            $table->decimal('promotion_discount_total', 18, 2)->default(0)->after('promotion_code');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('promotion_id');
            $table->dropColumn(['promotion_code', 'promotion_discount_total']);
        });

        Schema::dropIfExists('promotions');
    }
};
