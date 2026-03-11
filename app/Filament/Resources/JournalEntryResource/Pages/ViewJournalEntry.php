<?php

namespace App\Filament\Resources\JournalEntryResource\Pages;

use App\Enums\JournalStatus;
use App\Filament\Resources\JournalEntryResource;
use App\Services\AccountingService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewJournalEntry extends ViewRecord
{
    protected static string $resource = JournalEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('post')
                ->label('Posting')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Posting Jurnal')
                ->modalDescription('Jurnal yang sudah diposting akan mempengaruhi saldo GL. Lanjutkan?')
                ->visible(fn () => $this->record->status === JournalStatus::Draft && $this->record->canBeApprovedBy(auth()->user()))
                ->action(function () {
                    try {
                        app(AccountingService::class)->postJournal($this->record, auth()->user());
                        Notification::make()->title('Jurnal berhasil diposting')->success()->send();
                        $this->refreshFormData(['status', 'approval_status', 'posted_at']);
                    } catch (\InvalidArgumentException $e) {
                        Notification::make()->title('Gagal')->body($e->getMessage())->danger()->send();
                    }
                }),

            Action::make('reverse')
                ->label('Batalkan')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('danger')
                ->form([
                    Textarea::make('reason')
                        ->label('Alasan Pembatalan')
                        ->required(),
                ])
                ->visible(fn () => $this->record->status === JournalStatus::Posted)
                ->action(function (array $data) {
                    try {
                        app(AccountingService::class)->reverseJournal($this->record, auth()->user(), $data['reason']);
                        Notification::make()->title('Jurnal berhasil dibatalkan')->success()->send();
                        $this->refreshFormData(['status', 'reversed_by', 'reversed_at', 'reversal_reason']);
                    } catch (\InvalidArgumentException $e) {
                        Notification::make()->title('Gagal')->body($e->getMessage())->danger()->send();
                    }
                }),
        ];
    }
}
