<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class JournalLine extends Model
{
    protected $table = 'journal_entry_lines';

    protected $fillable = ['journal_entry_id', 'account_id', 'description', 'debit', 'credit'];

    protected function casts(): array
    {
        return ['debit' => 'decimal:2', 'credit' => 'decimal:2'];
    }

    protected static function booted(): void
    {
        static::saving(function (JournalLine $line): void {
            $journal = $line->journalEntry()->first();
            $account = $line->account()->first();
            $debit = round((float) $line->debit, 2);
            $credit = round((float) $line->credit, 2);

            if (! $journal || ! $account || (int) $journal->business_id !== (int) $account->business_id) {
                throw ValidationException::withMessages([
                    'account_id' => 'Journal line account must belong to the journal business.',
                ]);
            }

            if (($debit > 0 && $credit > 0) || ($debit <= 0 && $credit <= 0)) {
                throw ValidationException::withMessages([
                    'debit' => 'Fill either debit or credit with an amount greater than zero.',
                ]);
            }
        });
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
