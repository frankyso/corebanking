<?php

namespace App\Filament\Resources\DepositAccountResource\Pages;

use App\Actions\Deposit\EarlyWithdrawDeposit;
use App\Actions\Deposit\PledgeDeposit;
use App\Actions\Deposit\ProcessDepositMaturity;
use App\Actions\Deposit\UnpledgeDeposit;
use App\Enums\DepositStatus;
use App\Exceptions\DomainException;
use App\Filament\Resources\DepositAccountResource;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewDepositAccount extends ViewRecord
{
    protected static string $resource = DepositAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('processMaturity')
                ->label('Proses Jatuh Tempo')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Proses Jatuh Tempo')
                ->modalDescription('Apakah Anda yakin ingin memproses jatuh tempo deposito ini?')
                ->visible(fn (): bool => $this->record->status === DepositStatus::Active && $this->record->isMatured())
                ->action(function (): void {
                    try {
                        app(ProcessDepositMaturity::class)->execute($this->record, auth()->user());
                        Notification::make()->title('Jatuh tempo berhasil diproses')->success()->send();
                        $this->refreshFormData(['status', 'principal_amount', 'interest_rate', 'placement_date', 'maturity_date', 'total_interest_paid', 'total_tax_paid']);
                    } catch (DomainException $e) {
                        Notification::make()->title('Gagal')->body($e->getMessage())->danger()->send();
                    }
                }),

            Action::make('earlyWithdrawal')
                ->label('Pencairan Dini')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Pencairan Dini')
                ->modalDescription(function (): string {
                    $penaltyRate = $this->record->depositProduct->penalty_rate;

                    return "Deposito akan dicairkan sebelum jatuh tempo dengan penalti {$penaltyRate}%. Lanjutkan?";
                })
                ->visible(fn (): bool => $this->record->status === DepositStatus::Active && ! $this->record->is_pledged)
                ->action(function (): void {
                    try {
                        app(EarlyWithdrawDeposit::class)->execute($this->record, auth()->user());
                        Notification::make()->title('Deposito berhasil dicairkan')->success()->send();
                        $this->refreshFormData(['status']);
                    } catch (DomainException $e) {
                        Notification::make()->title('Gagal')->body($e->getMessage())->danger()->send();
                    }
                }),

            Action::make('pledge')
                ->label('Jaminkan')
                ->icon('heroicon-o-lock-closed')
                ->color('danger')
                ->form([
                    TextInput::make('pledge_reference')
                        ->label('Referensi Jaminan')
                        ->required()
                        ->placeholder('Nomor kredit / referensi'),
                ])
                ->visible(fn (): bool => $this->record->status === DepositStatus::Active && ! $this->record->is_pledged)
                ->action(function (array $data): void {
                    try {
                        app(PledgeDeposit::class)->execute($this->record, $data['pledge_reference']);
                        Notification::make()->title('Deposito berhasil dijaminkan')->success()->send();
                        $this->refreshFormData(['is_pledged', 'pledge_reference']);
                    } catch (DomainException $e) {
                        Notification::make()->title('Gagal')->body($e->getMessage())->danger()->send();
                    }
                }),

            Action::make('unpledge')
                ->label('Lepas Jaminan')
                ->icon('heroicon-o-lock-open')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn () => $this->record->is_pledged)
                ->action(function (): void {
                    try {
                        app(UnpledgeDeposit::class)->execute($this->record);
                        Notification::make()->title('Jaminan berhasil dilepas')->success()->send();
                        $this->refreshFormData(['is_pledged', 'pledge_reference']);
                    } catch (DomainException $e) {
                        Notification::make()->title('Gagal')->body($e->getMessage())->danger()->send();
                    }
                }),
        ];
    }
}
