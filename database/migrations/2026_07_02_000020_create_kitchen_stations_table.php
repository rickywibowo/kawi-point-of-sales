<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kitchen_stations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('code');
            $table->string('name');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['business_id', 'branch_id', 'code']);
            $table->index(['business_id', 'branch_id', 'is_active']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('kitchen_station_id')->nullable()->after('tax_id')->constrained('kitchen_stations')->nullOnDelete();
        });

        Schema::table('kitchen_ticket_items', function (Blueprint $table) {
            $table->foreignId('kitchen_station_id')->nullable()->after('sale_item_id')->constrained('kitchen_stations')->nullOnDelete();
            $table->string('station_name')->nullable()->after('product_name');
            $table->string('course')->default('main')->after('station_name');
            $table->unsignedInteger('station_sequence')->default(0)->after('course');
        });
    }

    public function down(): void
    {
        Schema::table('kitchen_ticket_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('kitchen_station_id');
            $table->dropColumn(['station_name', 'course', 'station_sequence']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('kitchen_station_id');
        });

        Schema::dropIfExists('kitchen_stations');
    }
};
