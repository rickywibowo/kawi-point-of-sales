<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class JournalEntry extends Model
{
    use BelongsToTenant, SoftDeletes, UsesUuid;

    protected $fillable = [
        'business_id',
        'branch_id',
        'outlet_id',
        'accounting_period_id',
        'uuid',
        'journal_number',
        'reference_no',
        'journal_date',
        'entry_date',
        'status',
        'source_type',
        'source_id',
        'description',
        'total_debit',
        'total_credit',
        'posted_at',
        'posted_by',
    ];

    protected function casts(): array
    {
        return [
            'journal_date' => 'date',
            'entry_date' => 'date',
            'total_debit' => 'decimal:2',
            'total_credit' => 'decimal:2',
            'posted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (JournalEntry $entry): void {
            $entry->outlet_id ??= $entry->branch_id;
            $entry->branch_id ??= $entry->outlet_id;
            $entry->entry_date ??= $entry->journal_date;
            $entry->journal_date ??= $entry->entry_date;
            $entry->reference_no ??= $entry->journal_number;
            $entry->journal_number ??= $entry->reference_no;
        });
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'outlet_id');
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }
}
