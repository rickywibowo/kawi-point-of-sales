<?php

namespace App\Filament\Resources\JournalEntries\Pages;

use App\Filament\Resources\JournalEntries\JournalEntryResource;
use App\Filament\Support\TenantContext;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateJournalEntry extends CreateRecord
{
    protected static string $resource = JournalEntryResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $reference = $data['reference_no'] ?: 'JE-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4));

        $data['business_id'] = TenantContext::businessId();
        $data['outlet_id'] = TenantContext::branchId();
        $data['branch_id'] = TenantContext::branchId();
        $data['journal_date'] = $data['entry_date'];
        $data['journal_number'] = $reference;
        $data['reference_no'] = $reference;
        $data['status'] = 'draft';

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->refreshTotals();
    }

    private function refreshTotals(): void
    {
        $this->record->load('lines');
        $this->record->update([
            'total_debit' => $this->record->lines->sum('debit'),
            'total_credit' => $this->record->lines->sum('credit'),
        ]);
    }
}
