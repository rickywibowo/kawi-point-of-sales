<?php

namespace App\Filament\Resources\JournalEntries;

use App\Filament\Resources\JournalEntries\Pages\CreateJournalEntry;
use App\Filament\Resources\JournalEntries\Pages\EditJournalEntry;
use App\Filament\Resources\JournalEntries\Pages\ListJournalEntries;
use App\Filament\Resources\JournalEntries\Pages\ViewJournalEntry;
use App\Filament\Resources\JournalEntries\Schemas\JournalEntryForm;
use App\Filament\Resources\JournalEntries\Tables\JournalEntriesTable;
use App\Filament\Support\TenantContext;
use App\Models\JournalEntry;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class JournalEntryResource extends Resource
{
    protected static ?string $model = JournalEntry::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'Journal Entries';

    protected static string|UnitEnum|null $navigationGroup = 'Accounting';

    public static function getEloquentQuery(): Builder
    {
        $businessId = TenantContext::businessId();
        $outletId = TenantContext::branchId();

        return parent::getEloquentQuery()
            ->when($businessId, fn (Builder $query) => $query->where('business_id', $businessId))
            ->when($outletId, fn (Builder $query) => $query->where(function (Builder $query) use ($outletId): void {
                $query->whereNull('outlet_id')->orWhere('outlet_id', $outletId);
            }));
    }

    public static function canEdit(Model $record): bool
    {
        return $record->status === 'draft';
    }

    public static function form(Schema $schema): Schema
    {
        return JournalEntryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return JournalEntriesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListJournalEntries::route('/'),
            'create' => CreateJournalEntry::route('/create'),
            'view' => ViewJournalEntry::route('/{record}'),
            'edit' => EditJournalEntry::route('/{record}/edit'),
        ];
    }
}
