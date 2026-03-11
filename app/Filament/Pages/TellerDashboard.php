<?php

namespace App\Filament\Pages;

use App\Models\LoanAccount;
use App\Models\SavingsAccount;
use App\Models\TellerSession;
use App\Models\Vault;
use App\Services\TellerService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use UnitEnum;

class TellerDashboard extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-computer-desktop';

    protected static string|UnitEnum|null $navigationGroup = 'Teller';

    protected static ?int $navigationSort = 30;

    protected static ?string $navigationLabel = 'Dashboard Teller';

    protected static ?string $title = 'Dashboard Teller';

    protected string $view = 'filament.pages.teller-dashboard';

    #[Computed]
    public function activeSession(): ?TellerSession
    {
        return app(TellerService::class)->getActiveSession(auth()->user());
    }

    #[Computed]
    public function recentTransactions(): Collection
    {
        $session = $this->activeSession;
        if (! $session) {
            return collect();
        }

        return $session->transactions()->with('customer')->latest()->limit(10)->get();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('openSession')
                ->label('Buka Sesi')
                ->icon('heroicon-o-play')
                ->color('success')
                ->form([
                    Select::make('vault_id')
                        ->label('Vault/Brankas')
                        ->options(Vault::query()->active()->pluck('name', 'id'))
                        ->required(),
                    TextInput::make('opening_balance')
                        ->label('Kas Awal')
                        ->numeric()
                        ->prefix('Rp')
                        ->required()
                        ->minValue(0),
                ])
                ->visible(fn () => ! $this->activeSession)
                ->action(function (array $data) {
                    try {
                        $vault = Vault::findOrFail($data['vault_id']);
                        app(TellerService::class)->openSession(
                            teller: auth()->user(),
                            vault: $vault,
                            openingBalance: (float) $data['opening_balance'],
                        );
                        Notification::make()->title('Sesi teller berhasil dibuka')->success()->send();
                        unset($this->activeSession, $this->recentTransactions);
                    } catch (\InvalidArgumentException $e) {
                        Notification::make()->title('Gagal')->body($e->getMessage())->danger()->send();
                    }
                }),

            Action::make('closeSession')
                ->label('Tutup Sesi')
                ->icon('heroicon-o-stop')
                ->color('danger')
                ->form([
                    Textarea::make('closing_notes')
                        ->label('Catatan Penutupan')
                        ->rows(3),
                ])
                ->visible(fn () => (bool) $this->activeSession)
                ->requiresConfirmation()
                ->modalHeading('Tutup Sesi Teller')
                ->modalDescription(fn () => $this->activeSession
                    ? 'Saldo kas saat ini: Rp '.number_format((float) $this->activeSession->current_balance, 0, ',', '.').'. Kas akan dikembalikan ke vault.'
                    : '')
                ->action(function (array $data) {
                    try {
                        app(TellerService::class)->closeSession(
                            session: $this->activeSession,
                            performer: auth()->user(),
                            notes: $data['closing_notes'] ?? null,
                        );
                        Notification::make()->title('Sesi teller berhasil ditutup')->success()->send();
                        unset($this->activeSession, $this->recentTransactions);
                    } catch (\InvalidArgumentException $e) {
                        Notification::make()->title('Gagal')->body($e->getMessage())->danger()->send();
                    }
                }),

            Action::make('deposit')
                ->label('Setor Tabungan')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->form([
                    Select::make('savings_account_id')
                        ->label('Rekening Tabungan')
                        ->options(SavingsAccount::query()->active()->with('customer')->get()->mapWithKeys(
                            fn (SavingsAccount $acc) => [$acc->id => "{$acc->account_number} - {$acc->customer?->display_name}"]
                        ))
                        ->searchable()
                        ->required(),
                    TextInput::make('amount')
                        ->label('Jumlah')
                        ->numeric()
                        ->prefix('Rp')
                        ->required()
                        ->minValue(1),
                    TextInput::make('description')
                        ->label('Keterangan')
                        ->maxLength(255),
                ])
                ->visible(fn () => (bool) $this->activeSession)
                ->action(function (array $data) {
                    try {
                        $account = SavingsAccount::findOrFail($data['savings_account_id']);
                        app(TellerService::class)->processDeposit(
                            session: $this->activeSession,
                            account: $account,
                            amount: (float) $data['amount'],
                            performer: auth()->user(),
                            description: $data['description'] ?? null,
                        );
                        Notification::make()->title('Setoran berhasil')->success()->send();
                        unset($this->activeSession, $this->recentTransactions);
                    } catch (\InvalidArgumentException $e) {
                        Notification::make()->title('Gagal')->body($e->getMessage())->danger()->send();
                    }
                }),

            Action::make('withdraw')
                ->label('Tarik Tabungan')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('warning')
                ->form([
                    Select::make('savings_account_id')
                        ->label('Rekening Tabungan')
                        ->options(SavingsAccount::query()->active()->with('customer')->get()->mapWithKeys(
                            fn (SavingsAccount $acc) => [$acc->id => "{$acc->account_number} - {$acc->customer?->display_name}"]
                        ))
                        ->searchable()
                        ->required(),
                    TextInput::make('amount')
                        ->label('Jumlah')
                        ->numeric()
                        ->prefix('Rp')
                        ->required()
                        ->minValue(1),
                    TextInput::make('description')
                        ->label('Keterangan')
                        ->maxLength(255),
                ])
                ->visible(fn () => (bool) $this->activeSession)
                ->action(function (array $data) {
                    try {
                        $account = SavingsAccount::findOrFail($data['savings_account_id']);
                        app(TellerService::class)->processWithdrawal(
                            session: $this->activeSession,
                            account: $account,
                            amount: (float) $data['amount'],
                            performer: auth()->user(),
                            description: $data['description'] ?? null,
                        );
                        Notification::make()->title('Penarikan berhasil')->success()->send();
                        unset($this->activeSession, $this->recentTransactions);
                    } catch (\InvalidArgumentException $e) {
                        Notification::make()->title('Gagal')->body($e->getMessage())->danger()->send();
                    }
                }),

            Action::make('loanPayment')
                ->label('Bayar Angsuran')
                ->icon('heroicon-o-banknotes')
                ->color('info')
                ->form([
                    Select::make('loan_account_id')
                        ->label('Rekening Kredit')
                        ->options(LoanAccount::query()->active()->with('customer')->get()->mapWithKeys(
                            fn (LoanAccount $acc) => [$acc->id => "{$acc->account_number} - {$acc->customer?->display_name}"]
                        ))
                        ->searchable()
                        ->required(),
                    TextInput::make('amount')
                        ->label('Jumlah')
                        ->numeric()
                        ->prefix('Rp')
                        ->required()
                        ->minValue(1),
                    TextInput::make('description')
                        ->label('Keterangan')
                        ->maxLength(255),
                ])
                ->visible(fn () => (bool) $this->activeSession)
                ->action(function (array $data) {
                    try {
                        $loanAccount = LoanAccount::findOrFail($data['loan_account_id']);
                        app(TellerService::class)->processLoanPayment(
                            session: $this->activeSession,
                            loanAccount: $loanAccount,
                            amount: (float) $data['amount'],
                            performer: auth()->user(),
                            description: $data['description'] ?? null,
                        );
                        Notification::make()->title('Pembayaran angsuran berhasil')->success()->send();
                        unset($this->activeSession, $this->recentTransactions);
                    } catch (\InvalidArgumentException $e) {
                        Notification::make()->title('Gagal')->body($e->getMessage())->danger()->send();
                    }
                }),
        ];
    }
}
