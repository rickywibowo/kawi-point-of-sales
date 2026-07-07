<?php

namespace Tests\Feature\Accounting;

use App\Filament\Resources\Accounts\AccountResource;
use App\Filament\Resources\JournalEntries\JournalEntryResource;
use App\Filament\Resources\JournalEntries\Tables\JournalEntriesTable;
use App\Models\Account;
use App\Models\Branch;
use App\Models\Business;
use App\Models\JournalEntry;
use App\Models\OutletAccountMapping;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ModernAccountingFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_accounts_are_created_per_business(): void
    {
        $this->seed(DatabaseSeeder::class);

        foreach (['KCF', 'WG', 'LBY'] as $code) {
            $business = Business::query()->where('code', $code)->firstOrFail();

            $this->assertDatabaseHas('accounts', [
                'business_id' => $business->id,
                'code' => '1-1000',
                'name' => 'Cash',
                'type' => 'asset',
                'normal_balance' => 'debit',
                'is_cash_account' => true,
            ]);
        }
    }

    public function test_account_code_is_unique_per_business(): void
    {
        $this->seed(DatabaseSeeder::class);
        $kcf = Business::query()->where('code', 'KCF')->firstOrFail();
        $wg = Business::query()->where('code', 'WG')->firstOrFail();

        Account::query()->create([
            'business_id' => $wg->id,
            'code' => '9-9999',
            'name' => 'WG Test Account',
            'type' => 'asset',
            'normal_balance' => 'debit',
        ]);

        Account::query()->create([
            'business_id' => $kcf->id,
            'code' => '9-9999',
            'name' => 'KCF Test Account',
            'type' => 'asset',
            'normal_balance' => 'debit',
        ]);

        $this->expectException(QueryException::class);

        Account::query()->create([
            'business_id' => $kcf->id,
            'code' => '9-9999',
            'name' => 'Duplicate KCF Account',
            'type' => 'asset',
            'normal_balance' => 'debit',
        ]);
    }

    public function test_account_resource_only_shows_accounts_from_active_business(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::query()->where('email', 'owner@kawipos.local')->firstOrFail();
        $business = Business::query()->where('code', 'KCF')->firstOrFail();
        $user->forceFill(['current_business_id' => $business->id])->save();
        $this->actingAs($user);

        $businessIds = AccountResource::getEloquentQuery()
            ->select('business_id')
            ->distinct()
            ->pluck('business_id')
            ->all();

        $this->assertSame([$business->id], $businessIds);
    }

    public function test_account_parent_must_belong_to_same_business(): void
    {
        $this->seed(DatabaseSeeder::class);
        $kcf = Business::query()->where('code', 'KCF')->firstOrFail();
        $wg = Business::query()->where('code', 'WG')->firstOrFail();
        $wgParent = Account::query()->where('business_id', $wg->id)->where('code', '1-1000')->firstOrFail();

        $this->expectException(ValidationException::class);

        Account::query()->create([
            'business_id' => $kcf->id,
            'parent_id' => $wgParent->id,
            'code' => '9-1000',
            'name' => 'Bad Parent',
            'type' => 'asset',
            'normal_balance' => 'debit',
        ]);
    }

    public function test_outlet_account_mapping_uses_active_business_and_outlet(): void
    {
        $this->seed(DatabaseSeeder::class);
        $business = Business::query()->where('code', 'KCF')->firstOrFail();
        $outlet = Branch::query()->where('business_id', $business->id)->where('code', 'KCF-01')->firstOrFail();
        $cash = Account::query()->where('business_id', $business->id)->where('code', '1-1000')->firstOrFail();

        $mapping = OutletAccountMapping::query()->where([
            'business_id' => $business->id,
            'outlet_id' => $outlet->id,
            'account_purpose' => 'cash',
        ])->firstOrFail();

        $this->assertSame($cash->id, $mapping->account_id);
        $this->assertTrue($mapping->is_active);
    }

    public function test_mapping_account_must_belong_to_active_business(): void
    {
        $this->seed(DatabaseSeeder::class);
        $kcf = Business::query()->where('code', 'KCF')->firstOrFail();
        $wg = Business::query()->where('code', 'WG')->firstOrFail();
        $outlet = Branch::query()->where('business_id', $kcf->id)->where('code', 'KCF-01')->firstOrFail();
        $wgCash = Account::query()->where('business_id', $wg->id)->where('code', '1-1000')->firstOrFail();

        $this->expectException(ValidationException::class);

        OutletAccountMapping::query()->create([
            'business_id' => $kcf->id,
            'outlet_id' => $outlet->id,
            'account_purpose' => 'bank',
            'account_id' => $wgCash->id,
        ]);
    }

    public function test_journal_entry_can_be_created_as_draft(): void
    {
        $this->seed(DatabaseSeeder::class);
        [$business, $outlet, $cash, $capital] = $this->journalContext();

        $journal = $this->draftJournal($business, $outlet);
        $journal->lines()->create(['account_id' => $cash->id, 'debit' => 100000, 'credit' => 0]);
        $journal->lines()->create(['account_id' => $capital->id, 'debit' => 0, 'credit' => 90000]);

        $this->assertSame('draft', $journal->status);
        $this->assertCount(2, $journal->lines()->get());
    }

    public function test_journal_entry_cannot_be_posted_if_unbalanced(): void
    {
        $this->seed(DatabaseSeeder::class);
        [$business, $outlet, $cash, $capital] = $this->journalContext();
        $journal = $this->draftJournal($business, $outlet);
        $journal->lines()->create(['account_id' => $cash->id, 'debit' => 100000, 'credit' => 0]);
        $journal->lines()->create(['account_id' => $capital->id, 'debit' => 0, 'credit' => 90000]);

        $this->expectException(ValidationException::class);

        JournalEntriesTable::post($journal);
    }

    public function test_balanced_journal_entry_can_be_posted(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->actingAs(User::query()->where('email', 'owner@kawipos.local')->firstOrFail());
        [$business, $outlet, $cash, $capital] = $this->journalContext();
        $journal = $this->draftJournal($business, $outlet);
        $journal->lines()->create(['account_id' => $cash->id, 'debit' => 100000, 'credit' => 0]);
        $journal->lines()->create(['account_id' => $capital->id, 'debit' => 0, 'credit' => 100000]);

        JournalEntriesTable::post($journal);

        $journal->refresh();
        $this->assertSame('posted', $journal->status);
        $this->assertSame('100000.00', (string) $journal->total_debit);
        $this->assertSame('100000.00', (string) $journal->total_credit);
        $this->assertNotNull($journal->posted_at);
    }

    public function test_posted_journal_entry_cannot_be_edited_in_filament(): void
    {
        $this->seed(DatabaseSeeder::class);
        [$business, $outlet] = $this->journalContext();
        $journal = $this->draftJournal($business, $outlet, ['status' => 'posted']);

        $this->assertFalse(JournalEntryResource::canEdit($journal));
    }

    private function journalContext(): array
    {
        $business = Business::query()->where('code', 'KCF')->firstOrFail();
        $outlet = Branch::query()->where('business_id', $business->id)->where('code', 'KCF-01')->firstOrFail();
        $cash = Account::query()->where('business_id', $business->id)->where('code', '1-1000')->firstOrFail();
        $capital = Account::query()->where('business_id', $business->id)->where('code', '3-1000')->firstOrFail();

        return [$business, $outlet, $cash, $capital];
    }

    private function draftJournal(Business $business, Branch $outlet, array $attributes = []): JournalEntry
    {
        $reference = $attributes['reference_no'] ?? 'JE-FOUNDATION-'.Str::upper(Str::random(6));

        return JournalEntry::query()->create(array_merge([
            'business_id' => $business->id,
            'branch_id' => $outlet->id,
            'outlet_id' => $outlet->id,
            'journal_number' => $reference,
            'reference_no' => $reference,
            'journal_date' => now()->toDateString(),
            'entry_date' => now()->toDateString(),
            'status' => 'draft',
            'description' => 'Foundation journal test',
        ], $attributes));
    }
}
