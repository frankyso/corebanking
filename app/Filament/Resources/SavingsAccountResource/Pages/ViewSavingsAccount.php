<?php

namespace App\Filament\Resources\SavingsAccountResource\Pages;

use App\Enums\SavingsAccountStatus;
use App\Filament\Resources\SavingsAccountResource;
use App\Services\SavingsService;
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
                ->visible(fn () => in_array($this->record->status, [SavingsAccountStatus::Active, SavingsAccountStatus::Dormant]))
                ->action(function (array $data) {
                    try {
                        app(SavingsService::class)->deposit(
                            account: $this->record,
                            amount: (float) $data['amount'],
                            performer: auth()->user(),
                            description: $data['description'] ?? null,
                        );

                        Notification::make()->title('Setoran berhasil')->success()->send();
                        $this->refreshFormData(['balance', 'available_balance', 'status']);
                    } catch (\InvalidArgumentException $e) {
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
                ->visible(fn () => in_array($this->record->status, [SavingsAccountStatus::Active, SavingsAccountStatus::Dormant]))
                ->action(function (array $data) {
                    try {
                        app(SavingsService::class)->withdraw(
                            account: $this->record,
                            amount: (float) $data['amount'],
                            performer: auth()->user(),
                            description: $data['description'] ?? null,
                        );

                        Notification::make()->title('Penarikan berhasil')->success()->send();
                        $this->refreshFormData(['balance', 'available_balance']);
                    } catch (\InvalidArgumentException $e) {
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
                ->visible(fn () => $this->record->status === SavingsAccountStatus::Active)
                ->action(function (array $data) {
                    try {
                        app(SavingsService::class)->hold(
                            account: $this->record,
                            amount: (float) $data['amount'],
                            performer: auth()->user(),
                        );

                        Notification::make()->title('Saldo berhasil diblokir')->success()->send();
                        $this->refreshFormData(['hold_amount', 'available_balance']);
                    } catch (\InvalidArgumentException $e) {
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
                ->visible(fn () => (float) $this->record->hold_amount > 0)
                ->action(function (array $data) {
                    try {
                        app(SavingsService::class)->unhold(
                            account: $this->record,
                            amount: (float) $data['amount'],
                            performer: auth()->user(),
                        );

                        Notification::make()->title('Blokir berhasil dibuka')->success()->send();
                        $this->refreshFormData(['hold_amount', 'available_balance']);
                    } catch (\InvalidArgumentException $e) {
                        Notification::make()->title('Gagal')->body($e->getMessage())->danger()->send();
                    }
                }),

            Action::make('freeze')
                ->label('Bekukan')
                ->icon('heroicon-o-no-symbol')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn () => $this->record->status === SavingsAccountStatus::Active)
                ->action(function () {
                    app(SavingsService::class)->freeze($this->record);
                    Notification::make()->title('Rekening dibekukan')->warning()->send();
                    $this->refreshFormData(['status']);
                }),

            Action::make('unfreeze')
                ->label('Buka Bekuan')
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn () => $this->record->status === SavingsAccountStatus::Frozen)
                ->action(function () {
                    app(SavingsService::class)->unfreeze($this->record);
                    Notification::make()->title('Bekuan dibuka')->success()->send();
                    $this->refreshFormData(['status']);
                }),

            Action::make('close')
                ->label('Tutup Rekening')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Tutup Rekening')
                ->modalDescription('Apakah Anda yakin ingin menutup rekening ini? Saldo tersisa akan dikembalikan.')
                ->visible(fn () => in_array($this->record->status, [SavingsAccountStatus::Active, SavingsAccountStatus::Dormant]))
                ->action(function () {
                    try {
                        app(SavingsService::class)->close($this->record, auth()->user());
                        Notification::make()->title('Rekening ditutup')->warning()->send();
                        $this->refreshFormData(['status', 'balance', 'available_balance']);
                    } catch (\InvalidArgumentException $e) {
                        Notification::make()->title('Gagal')->body($e->getMessage())->danger()->send();
                    }
                }),
        ];
    }
}
