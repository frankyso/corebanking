<?php

namespace App\Filament\Resources\JournalEntryResource\Pages;

use App\Actions\Accounting\PostJournalEntry;
use App\Actions\Accounting\ReverseJournalEntry;
use App\Enums\JournalStatus;
use App\Exceptions\DomainException;
use App\Filament\Resources\JournalEntryResource;
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
                ->visible(fn (): bool => $this->record->status === JournalStatus::Draft && $this->record->canBeApprovedBy(auth()->user()))
                ->action(function (): void {
                    try {
                        app(PostJournalEntry::class)->execute($this->record, auth()->user());
                        Notification::make()->title('Jurnal berhasil diposting')->success()->send();
                        $this->refreshFormData(['status', 'approval_status', 'posted_at']);
                    } catch (DomainException $e) {
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
                ->visible(fn (): bool => $this->record->status === JournalStatus::Posted)
                ->action(function (array $data): void {
                    try {
                        app(ReverseJournalEntry::class)->execute($this->record, auth()->user(), $data['reason']);
                        Notification::make()->title('Jurnal berhasil dibatalkan')->success()->send();
                        $this->refreshFormData(['status', 'reversed_by', 'reversed_at', 'reversal_reason']);
                    } catch (DomainException $e) {
                        Notification::make()->title('Gagal')->body($e->getMessage())->danger()->send();
                    }
                }),
        ];
    }
}
