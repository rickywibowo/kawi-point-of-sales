<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table): void {
            if (! Schema::hasColumn('accounts', 'is_cash_account')) {
                $table->boolean('is_cash_account')->default(false)->after('normal_balance');
            }

            if (! Schema::hasColumn('accounts', 'is_system')) {
                $table->boolean('is_system')->default(false)->after('is_cash_account');
            }

            if (! Schema::hasColumn('accounts', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        if (Schema::hasColumn('accounts', 'is_cash')) {
            DB::table('accounts')->where('is_cash', true)->update(['is_cash_account' => true]);
        }

        Schema::table('journal_entries', function (Blueprint $table): void {
            if (! Schema::hasColumn('journal_entries', 'outlet_id')) {
                $table->foreignId('outlet_id')->nullable()->after('branch_id')->constrained('branches')->nullOnDelete();
            }

            if (! Schema::hasColumn('journal_entries', 'entry_date')) {
                $table->date('entry_date')->nullable()->after('journal_date');
            }

            if (! Schema::hasColumn('journal_entries', 'reference_no')) {
                $table->string('reference_no')->nullable()->after('journal_number');
            }

            if (! Schema::hasColumn('journal_entries', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        DB::table('journal_entries')
            ->whereNull('outlet_id')
            ->whereNotNull('branch_id')
            ->update(['outlet_id' => DB::raw('branch_id')]);

        DB::table('journal_entries')
            ->whereNull('entry_date')
            ->update(['entry_date' => DB::raw('journal_date')]);

        DB::table('journal_entries')
            ->whereNull('reference_no')
            ->update(['reference_no' => DB::raw('journal_number')]);

        if (! Schema::hasTable('journal_entry_lines')) {
            Schema::create('journal_entry_lines', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('journal_entry_id')->constrained()->cascadeOnDelete();
                $table->foreignId('account_id')->constrained()->restrictOnDelete();
                $table->string('description')->nullable();
                $table->decimal('debit', 15, 2)->default(0);
                $table->decimal('credit', 15, 2)->default(0);
                $table->timestamps();
            });

            if (Schema::hasTable('journal_lines')) {
                DB::table('journal_lines')->orderBy('id')->each(function (object $line): void {
                    DB::table('journal_entry_lines')->insert([
                        'id' => $line->id,
                        'journal_entry_id' => $line->journal_entry_id,
                        'account_id' => $line->account_id,
                        'description' => $line->description,
                        'debit' => $line->debit,
                        'credit' => $line->credit,
                        'created_at' => $line->created_at,
                        'updated_at' => $line->updated_at,
                    ]);
                });
            }
        }

        if (! Schema::hasTable('outlet_account_mappings')) {
            Schema::create('outlet_account_mappings', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('outlet_id')->constrained('branches')->cascadeOnDelete();
                $table->string('account_purpose');
                $table->foreignId('account_id')->constrained('accounts')->restrictOnDelete();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['business_id', 'outlet_id', 'account_purpose', 'is_active'], 'outlet_mapping_active_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('outlet_account_mappings');
        Schema::dropIfExists('journal_entry_lines');
    }
};
