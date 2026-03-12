<?php

namespace App\Filament\Resources\JournalEntryResource\Pages;

use App\Actions\Accounting\CreateJournalEntry as CreateJournalEntryAction;
use App\DTOs\Accounting\CreateJournalData;
use App\Enums\JournalSource;
use App\Exceptions\DomainException;
use App\Filament\Resources\JournalEntryResource;
use App\Models\Branch;
use App\Models\ChartOfAccount;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class CreateJournalEntry extends CreateRecord
{
    protected static string $resource = JournalEntryResource::class;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Header Jurnal')
                    ->schema([
                        DatePicker::make('journal_date')
                            ->label('Tanggal Jurnal')
                            ->default(now())
                            ->required(),
                        TextInput::make('description')
                            ->label('Keterangan')
                            ->required()
                            ->maxLength(255),
                        Select::make('source')
                            ->label('Sumber')
                            ->options(JournalSource::class)
                            ->default(JournalSource::Manual->value)
                            ->required(),
                        Select::make('branch_id')
                            ->label('Cabang')
                            ->options(Branch::query()->where('is_active', true)->pluck('name', 'id'))
                            ->default(fn () => auth()->user()->branch_id),
                    ])->columns(2),

                Section::make('Detail Jurnal')
                    ->schema([
                        Repeater::make('lines')
                            ->label('Baris Jurnal')
                            ->schema([
                                Select::make('account_id')
                                    ->label('Akun')
                                    ->options(
                                        ChartOfAccount::query()
                                            ->postable()
                                            ->get()
                                            ->mapWithKeys(fn (ChartOfAccount $coa): array => [$coa->id => "{$coa->account_code} - {$coa->account_name}"])
                                    )
                                    ->searchable()
                                    ->required(),
                                TextInput::make('description')
                                    ->label('Keterangan'),
                                TextInput::make('debit')
                                    ->label('Debit')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->default(0),
                                TextInput::make('credit')
                                    ->label('Kredit')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->default(0),
                            ])
                            ->columns(4)
                            ->minItems(2)
                            ->defaultItems(2)
                            ->reorderable(false),
                    ]),
            ]);
    }

    protected function handleRecordCreation(array $data): Model
    {
        try {
            $record = app(CreateJournalEntryAction::class)->execute(new CreateJournalData(
                journalDate: Carbon::parse($data['journal_date']),
                description: $data['description'],
                source: $data['source'] instanceof JournalSource
                    ? $data['source']
                    : JournalSource::from($data['source']),
                lines: collect($data['lines'])->map(fn (array $line): array => [
                    'account_id' => $line['account_id'],
                    'description' => $line['description'] ?? null,
                    'debit' => (float) ($line['debit'] ?? 0),
                    'credit' => (float) ($line['credit'] ?? 0),
                ])->toArray(),
                creator: auth()->user(),
                branchId: $data['branch_id'] ?? null,
            ));
        } catch (DomainException $e) {
            Notification::make()
                ->title('Gagal membuat jurnal')
                ->body($e->getMessage())
                ->danger()
                ->send();

            $this->halt();

            throw new \RuntimeException('Unreachable');
        }

        return $record;
    }
}
