<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('business_id')->constrained()->nullOnDelete();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('business_id')->constrained()->nullOnDelete();
        });

        DB::table('categories')
            ->whereNull('branch_id')
            ->orderBy('id')
            ->get(['id', 'business_id'])
            ->each(function (object $category): void {
                $branchId = DB::table('branches')
                    ->where('business_id', $category->business_id)
                    ->orderBy('id')
                    ->value('id');

                if ($branchId) {
                    DB::table('categories')->where('id', $category->id)->update(['branch_id' => $branchId]);
                }
            });

        DB::table('products')
            ->whereNull('branch_id')
            ->orderBy('id')
            ->get(['id', 'business_id'])
            ->each(function (object $product): void {
                $branchId = DB::table('branches')
                    ->where('business_id', $product->business_id)
                    ->orderBy('id')
                    ->value('id');

                if ($branchId) {
                    DB::table('products')->where('id', $product->id)->update(['branch_id' => $branchId]);
                }
            });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique(['business_id', 'slug']);
            $table->unique(['business_id', 'branch_id', 'slug']);
            $table->index(['business_id', 'branch_id']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['business_id', 'sku']);
            $table->dropUnique(['business_id', 'barcode']);
            $table->unique(['business_id', 'branch_id', 'sku']);
            $table->unique(['business_id', 'branch_id', 'barcode']);
            $table->index(['business_id', 'branch_id']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['business_id', 'branch_id', 'sku']);
            $table->dropUnique(['business_id', 'branch_id', 'barcode']);
            $table->dropIndex(['business_id', 'branch_id']);
            $table->unique(['business_id', 'sku']);
            $table->unique(['business_id', 'barcode']);
            $table->dropConstrainedForeignId('branch_id');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique(['business_id', 'branch_id', 'slug']);
            $table->dropIndex(['business_id', 'branch_id']);
            $table->unique(['business_id', 'slug']);
            $table->dropConstrainedForeignId('branch_id');
        });
    }
};
