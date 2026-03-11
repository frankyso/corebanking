<?php

namespace App\Filament\Resources;

use App\Enums\JournalSource;
use App\Enums\JournalStatus;
use App\Filament\Resources\JournalEntryResource\Pages;
use App\Filament\Resources\JournalEntryResource\RelationManagers;
use App\Models\JournalEntry;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class JournalEntryResource extends Resource
{
    protected static ?string $model = JournalEntry::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected static string|UnitEnum|null $navigationGroup = 'Akuntansi';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'Jurnal';

    protected static ?string $pluralModelLabel = 'Jurnal';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Jurnal')
                    ->schema([
                        TextEntry::make('journal_number')->label('No. Jurnal')->copyable(),
                        TextEntry::make('journal_date')->label('Tanggal')->date('d M Y'),
                        TextEntry::make('description')->label('Keterangan')->columnSpanFull(),
                        TextEntry::make('source')->label('Sumber')->badge(),
                        TextEntry::make('status')->label('Status')->badge(),
                        TextEntry::make('branch.name')->label('Cabang'),
                    ])
                    ->columns(2),
                Section::make('Total')
                    ->schema([
                        TextEntry::make('total_debit')->label('Total Debit')->money('IDR'),
                        TextEntry::make('total_credit')->label('Total Kredit')->money('IDR'),
                    ])
                    ->columns(2),
                Section::make('Riwayat')
                    ->schema([
                        TextEntry::make('creator.name')->label('Dibuat Oleh'),
                        TextEntry::make('created_at')->label('Tanggal Dibuat')->dateTime('d M Y H:i'),
                        TextEntry::make('posted_at')->label('Tanggal Posting')->dateTime('d M Y H:i')->placeholder('-'),
                        TextEntry::make('reversedBy.name')->label('Dibatalkan Oleh')->placeholder('-'),
                        TextEntry::make('reversed_at')->label('Tanggal Pembatalan')->dateTime('d M Y H:i')->placeholder('-'),
                        TextEntry::make('reversal_reason')->label('Alasan Pembatalan')->columnSpanFull()->placeholder('-'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('journal_number')
                    ->label('No. Jurnal')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                TextColumn::make('journal_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('description')
                    ->label('Keterangan')
                    ->limit(50)
                    ->searchable(),
                TextColumn::make('source')
                    ->label('Sumber')
                    ->badge(),
                TextColumn::make('total_debit')
                    ->label('Debit')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('total_credit')
                    ->label('Kredit')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('creator.name')
                    ->label('Dibuat Oleh')
                    ->placeholder('-'),
                TextColumn::make('posted_at')
                    ->label('Tgl Posting')
                    ->dateTime('d M Y H:i')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(JournalStatus::class),
                SelectFilter::make('source')
                    ->options(JournalSource::class),
                SelectFilter::make('branch_id')
                    ->label('Cabang')
                    ->relationship('branch', 'name'),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\LinesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJournalEntries::route('/'),
            'create' => Pages\CreateJournalEntry::route('/create'),
            'view' => Pages\ViewJournalEntry::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['creator', 'branch']);
    }
}
