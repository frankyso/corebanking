<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Actions\Customer\ApproveCustomer;
use App\Actions\Customer\RejectCustomer;
use App\Enums\ApprovalStatus;
use App\Enums\CustomerStatus;
use App\Exceptions\DomainException;
use App\Filament\Resources\CustomerResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewCustomer extends ViewRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            Action::make('approve')
                ->label('Setujui')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Setujui Nasabah')
                ->modalDescription('Apakah Anda yakin ingin menyetujui nasabah ini?')
                ->visible(fn (): bool => $this->record->approval_status === ApprovalStatus::Pending
                    && $this->record->canBeApprovedBy(auth()->user()))
                ->action(function (): void {
                    try {
                        app(ApproveCustomer::class)->execute($this->record, auth()->user());

                        Notification::make()
                            ->title('Nasabah berhasil disetujui')
                            ->success()
                            ->send();

                        $this->refreshFormData(['status', 'approval_status', 'approved_by', 'approved_at']);
                    } catch (DomainException $e) {
                        Notification::make()
                            ->title('Gagal menyetujui nasabah')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            Action::make('reject')
                ->label('Tolak')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->form([
                    Textarea::make('rejection_reason')
                        ->label('Alasan Penolakan')
                        ->required()
                        ->rows(3),
                ])
                ->visible(fn (): bool => $this->record->approval_status === ApprovalStatus::Pending
                    && $this->record->canBeApprovedBy(auth()->user()))
                ->action(function (array $data): void {
                    try {
                        app(RejectCustomer::class)->execute($this->record, auth()->user(), $data['rejection_reason']);

                        Notification::make()
                            ->title('Nasabah ditolak')
                            ->warning()
                            ->send();

                        $this->refreshFormData(['approval_status', 'rejection_reason']);
                    } catch (DomainException $e) {
                        Notification::make()
                            ->title('Gagal menolak nasabah')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            Action::make('block')
                ->label('Blokir')
                ->icon('heroicon-o-no-symbol')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->record->status === CustomerStatus::Active)
                ->action(function (): void {
                    $this->record->block();

                    Notification::make()
                        ->title('Nasabah diblokir')
                        ->warning()
                        ->send();

                    $this->refreshFormData(['status']);
                }),
            Action::make('unblock')
                ->label('Buka Blokir')
                ->icon('heroicon-o-lock-open')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->record->status === CustomerStatus::Blocked)
                ->action(function (): void {
                    $this->record->unblock();

                    Notification::make()
                        ->title('Blokir nasabah dibuka')
                        ->success()
                        ->send();

                    $this->refreshFormData(['status']);
                }),
        ];
    }
}
