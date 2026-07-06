<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permissions', function (Blueprint $table): void {
            if (! Schema::hasColumn('permissions', 'guard_name')) {
                $table->string('guard_name')->default('web')->after('name');
            }
        });

        Schema::table('roles', function (Blueprint $table): void {
            if (! Schema::hasColumn('roles', 'guard_name')) {
                $table->string('guard_name')->default('web')->after('name');
            }
        });

        Schema::create('role_has_permissions', function (Blueprint $table): void {
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();

            $table->primary(['permission_id', 'role_id'], 'role_has_permissions_permission_id_role_id_primary');
        });

        Schema::create('model_has_permissions', function (Blueprint $table): void {
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');

            $table->index(['model_id', 'model_type'], 'model_has_permissions_model_id_model_type_index');
            $table->primary(['permission_id', 'model_id', 'model_type'], 'model_has_permissions_permission_model_type_primary');
        });

        Schema::create('model_has_roles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->foreignId('business_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->index(['model_id', 'model_type'], 'model_has_roles_model_id_model_type_index');
            $table->unique(['role_id', 'model_type', 'model_id', 'business_id', 'branch_id'], 'model_has_roles_scope_unique');
        });

        DB::table('permission_role')
            ->orderBy('id')
            ->get(['permission_id', 'role_id'])
            ->each(function ($row): void {
                DB::table('role_has_permissions')->updateOrInsert([
                    'permission_id' => $row->permission_id,
                    'role_id' => $row->role_id,
                ]);
            });

        DB::table('role_user')
            ->orderBy('id')
            ->get(['user_id', 'role_id', 'business_id', 'branch_id', 'created_at', 'updated_at'])
            ->each(function ($row): void {
                DB::table('model_has_roles')->updateOrInsert([
                    'role_id' => $row->role_id,
                    'model_type' => User::class,
                    'model_id' => $row->user_id,
                    'business_id' => $row->business_id,
                    'branch_id' => $row->branch_id,
                ], [
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('model_has_permissions');
        Schema::dropIfExists('role_has_permissions');

        Schema::table('roles', function (Blueprint $table): void {
            if (Schema::hasColumn('roles', 'guard_name')) {
                $table->dropColumn('guard_name');
            }
        });

        Schema::table('permissions', function (Blueprint $table): void {
            if (Schema::hasColumn('permissions', 'guard_name')) {
                $table->dropColumn('guard_name');
            }
        });
    }
};
