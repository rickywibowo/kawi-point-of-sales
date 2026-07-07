<?php

namespace App\Filament\Resources\JournalEntries\Pages;

use App\Filament\Resources\JournalEntries\JournalEntryResource;
use App\Filament\Resources\JournalEntries\Tables\JournalEntriesTable;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditJournalEntry extends EditRecord
{
    protected static string $resource = JournalEntryResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['business_id'], $data['outlet_id'], $data['branch_id'], $data['status']);
        $data['journal_date'] = $data['entry_date'];
        $data['journal_number'] = $data['reference_no'] ?: $this->record->journal_number;

        return $data;
    }

    protected function afterSave(): void
    {
        $this->record->load('lines');
        $this->record->update([
            'total_debit' => $this->record->lines->sum('debit'),
            'total_credit' => $this->record->lines->sum('credit'),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('post')
                ->icon('heroicon-o-check-circle')
                ->requiresConfirmation()
                ->visible(fn () => $this->record->status === 'draft')
                ->action(fn () => JournalEntriesTable::post($this->record)),
            DeleteAction::make()
                ->visible(fn () => $this->record->status === 'draft'),
        ];
    }
}
