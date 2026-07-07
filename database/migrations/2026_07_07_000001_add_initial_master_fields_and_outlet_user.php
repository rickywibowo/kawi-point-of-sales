<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table): void {
            if (! Schema::hasColumn('businesses', 'code')) {
                $table->string('code', 20)->nullable()->after('uuid');
            }

            if (! Schema::hasColumn('businesses', 'type')) {
                $table->string('type', 40)->nullable()->after('code');
            }
        });

        Schema::create('outlet_user', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['branch_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outlet_user');

        Schema::table('businesses', function (Blueprint $table): void {
            if (Schema::hasColumn('businesses', 'type')) {
                $table->dropColumn('type');
            }

            if (Schema::hasColumn('businesses', 'code')) {
                $table->dropColumn('code');
            }
        });
    }
};
