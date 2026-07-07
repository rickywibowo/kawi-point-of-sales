<?php

namespace App\Filament\Resources\JournalEntries\Tables;

use App\Models\JournalEntry;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;

class JournalEntriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('entry_date', 'desc')
            ->columns([
                TextColumn::make('entry_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('reference_no')
                    ->label('Reference')
                    ->searchable(),
                TextColumn::make('outlet.name')
                    ->label('Outlet')
                    ->searchable(),
                TextColumn::make('description')
                    ->limit(40)
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('total_debit')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('total_credit')
                    ->money('IDR')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'posted' => 'Posted',
                        'void' => 'Void',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (JournalEntry $record) => $record->status === 'draft'),
                Action::make('post')
                    ->icon('heroicon-o-check-circle')
                    ->requiresConfirmation()
                    ->visible(fn (JournalEntry $record) => $record->status === 'draft')
                    ->action(function (JournalEntry $record): void {
                        self::post($record);
                    }),
            ]);
    }

    public static function post(JournalEntry $record): void
    {
        $record->load('lines.account');
        $totalDebit = round($record->lines->sum(fn ($line) => (float) $line->debit), 2);
        $totalCredit = round($record->lines->sum(fn ($line) => (float) $line->credit), 2);
        $invalidLine = $record->lines->contains(function ($line) use ($record): bool {
            $debit = round((float) $line->debit, 2);
            $credit = round((float) $line->credit, 2);

            return ! $line->account
                || $line->account->business_id !== $record->business_id
                || ($debit > 0 && $credit > 0)
                || ($debit <= 0 && $credit <= 0);
        });

        if ($record->lines->count() < 2 || $invalidLine || $totalDebit <= 0 || $totalDebit !== $totalCredit) {
            throw ValidationException::withMessages([
                'lines' => 'Journal entry must have valid balanced debit and credit lines before posting.',
            ]);
        }

        $record->update([
            'status' => 'posted',
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'posted_at' => now(),
            'posted_by' => auth()->id(),
        ]);

        Notification::make()
            ->title('Journal entry posted')
            ->success()
            ->send();
    }
}
