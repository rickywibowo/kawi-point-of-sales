<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('legal_name')->nullable();
            $table->string('tax_number')->nullable();
            $table->string('currency', 3)->default('IDR');
            $table->string('timezone')->default('Asia/Makassar');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('code');
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['business_id', 'code']);
        });

        Schema::create('business_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_owner')->default(false);
            $table->timestamps();

            $table->unique(['business_id', 'user_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('current_business_id')->nullable()->after('password')->constrained('businesses')->nullOnDelete();
            $table->foreignId('current_branch_id')->nullable()->after('current_business_id')->constrained('branches')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('current_branch_id');
            $table->dropConstrainedForeignId('current_business_id');
        });

        Schema::dropIfExists('business_user');
        Schema::dropIfExists('branches');
        Schema::dropIfExists('businesses');
    }
};
