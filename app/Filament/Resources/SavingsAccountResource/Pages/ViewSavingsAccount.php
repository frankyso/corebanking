<?php

namespace App\Filament\Resources\SavingsAccountResource\Pages;

use App\Actions\Savings\CloseSavingsAccount;
use App\Actions\Savings\DepositToSavings;
use App\Actions\Savings\FreezeSavingsAccount;
use App\Actions\Savings\HoldSavingsBalance;
use App\Actions\Savings\UnfreezeSavingsAccount;
use App\Actions\Savings\UnholdSavingsBalance;
use App\Actions\Savings\WithdrawFromSavings;
use App\Enums\SavingsAccountStatus;
use App\Exceptions\DomainException;
use App\Filament\Resources\SavingsAccountResource;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewSavingsAccount extends ViewRecord
{
    protected static string $resource = SavingsAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('deposit')
                ->label('Setoran')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->form([
                    TextInput::make('amount')
                        ->label('Jumlah')
                        ->numeric()
                        ->prefix('Rp')
                        ->required()
                        ->minValue(1),
                    TextInput::make('description')
                        ->label('Keterangan'),
                ])
                ->visible(fn (): bool => in_array($this->record->status, [SavingsAccountStatus::Active, SavingsAccountStatus::Dormant]))
                ->action(function (array $data): void {
                    try {
                        app(DepositToSavings::class)->execute(
                            account: $this->record,
                            amount: (float) $data['amount'],
                            performer: auth()->user(),
                            description: $data['description'] ?? null,
                        );

                        Notification::make()->title('Setoran berhasil')->success()->send();
                        $this->refreshFormData(['balance', 'available_balance', 'status']);
                    } catch (DomainException $e) {
                        Notification::make()->title('Gagal')->body($e->getMessage())->danger()->send();
                    }
                }),

            Action::make('withdraw')
                ->label('Penarikan')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('warning')
                ->form([
                    TextInput::make('amount')
                        ->label('Jumlah')
                        ->numeric()
                        ->prefix('Rp')
                        ->required()
                        ->minValue(1),
                    TextInput::make('description')
                        ->label('Keterangan'),
                ])
                ->visible(fn (): bool => in_array($this->record->status, [SavingsAccountStatus::Active, SavingsAccountStatus::Dormant]))
                ->action(function (array $data): void {
                    try {
                        app(WithdrawFromSavings::class)->execute(
                            account: $this->record,
                            amount: (float) $data['amount'],
                            performer: auth()->user(),
                            description: $data['description'] ?? null,
                        );

                        Notification::make()->title('Penarikan berhasil')->success()->send();
                        $this->refreshFormData(['balance', 'available_balance']);
                    } catch (DomainException $e) {
                        Notification::make()->title('Gagal')->body($e->getMessage())->danger()->send();
                    }
                }),

            Action::make('hold')
                ->label('Blokir Saldo')
                ->icon('heroicon-o-lock-closed')
                ->color('danger')
                ->form([
                    TextInput::make('amount')
                        ->label('Jumlah')
                        ->numeric()
                        ->prefix('Rp')
                        ->required()
                        ->minValue(1),
                ])
                ->visible(fn (): bool => $this->record->status === SavingsAccountStatus::Active)
                ->action(function (array $data): void {
                    try {
                        app(HoldSavingsBalance::class)->execute(
                            account: $this->record,
                            amount: (float) $data['amount'],
                            performer: auth()->user(),
                        );

                        Notification::make()->title('Saldo berhasil diblokir')->success()->send();
                        $this->refreshFormData(['hold_amount', 'available_balance']);
                    } catch (DomainException $e) {
                        Notification::make()->title('Gagal')->body($e->getMessage())->danger()->send();
                    }
                }),

            Action::make('unhold')
                ->label('Buka Blokir')
                ->icon('heroicon-o-lock-open')
                ->color('success')
                ->form([
                    TextInput::make('amount')
                        ->label('Jumlah')
                        ->numeric()
                        ->prefix('Rp')
                        ->required()
                        ->minValue(1),
                ])
                ->visible(fn (): bool => (float) $this->record->hold_amount > 0)
                ->action(function (array $data): void {
                    try {
                        app(UnholdSavingsBalance::class)->execute(
                            account: $this->record,
                            amount: (float) $data['amount'],
                            performer: auth()->user(),
                        );

                        Notification::make()->title('Blokir berhasil dibuka')->success()->send();
                        $this->refreshFormData(['hold_amount', 'available_balance']);
                    } catch (DomainException $e) {
                        Notification::make()->title('Gagal')->body($e->getMessage())->danger()->send();
                    }
                }),

            Action::make('freeze')
                ->label('Bekukan')
                ->icon('heroicon-o-no-symbol')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->record->status === SavingsAccountStatus::Active)
                ->action(function (): void {
                    try {
                        app(FreezeSavingsAccount::class)->execute($this->record);
                        Notification::make()->title('Rekening dibekukan')->warning()->send();
                        $this->refreshFormData(['status']);
                    } catch (DomainException $e) {
                        Notification::make()->title('Gagal')->body($e->getMessage())->danger()->send();
                    }
                }),

            Action::make('unfreeze')
                ->label('Buka Bekuan')
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->record->status === SavingsAccountStatus::Frozen)
                ->action(function (): void {
                    try {
                        app(UnfreezeSavingsAccount::class)->execute($this->record);
                        Notification::make()->title('Bekuan dibuka')->success()->send();
                        $this->refreshFormData(['status']);
                    } catch (DomainException $e) {
                        Notification::make()->title('Gagal')->body($e->getMessage())->danger()->send();
                    }
                }),

            Action::make('close')
                ->label('Tutup Rekening')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Tutup Rekening')
                ->modalDescription('Apakah Anda yakin ingin menutup rekening ini? Saldo tersisa akan dikembalikan.')
                ->visible(fn (): bool => in_array($this->record->status, [SavingsAccountStatus::Active, SavingsAccountStatus::Dormant]))
                ->action(function (): void {
                    try {
                        app(CloseSavingsAccount::class)->execute($this->record, auth()->user());
                        Notification::make()->title('Rekening ditutup')->warning()->send();
                        $this->refreshFormData(['status', 'balance', 'available_balance']);
                    } catch (DomainException $e) {
                        Notification::make()->title('Gagal')->body($e->getMessage())->danger()->send();
                    }
                }),
        ];
    }
}
