<?php

namespace App\Filament\Resources\LoanApplicationResource\Pages;

use App\Actions\Loan\ApproveLoanApplication;
use App\Actions\Loan\DisburseLoan;
use App\Actions\Loan\RejectLoanApplication;
use App\DTOs\Loan\ApproveLoanApplicationData;
use App\Enums\LoanApplicationStatus;
use App\Exceptions\DomainException;
use App\Filament\Resources\LoanApplicationResource;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewLoanApplication extends ViewRecord
{
    protected static string $resource = LoanApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label('Setujui')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->form([
                    TextInput::make('approved_amount')
                        ->label('Jumlah Disetujui')
                        ->numeric()
                        ->prefix('Rp')
                        ->default(fn () => $this->record->requested_amount)
                        ->required(),
                    TextInput::make('approved_tenor')
                        ->label('Tenor Disetujui (bulan)')
                        ->numeric()
                        ->default(fn () => $this->record->requested_tenor_months)
                        ->required(),
                ])
                ->visible(fn (): bool => in_array($this->record->status, [LoanApplicationStatus::Submitted, LoanApplicationStatus::UnderReview])
                    && $this->record->created_by !== auth()->id())
                ->action(function (array $data): void {
                    try {
                        app(ApproveLoanApplication::class)->execute(new ApproveLoanApplicationData(
                            application: $this->record,
                            approver: auth()->user(),
                            approvedAmount: (float) $data['approved_amount'],
                            approvedTenor: (int) $data['approved_tenor'],
                        ));
                        Notification::make()->title('Permohonan berhasil disetujui')->success()->send();
                        $this->refreshFormData(['status', 'approved_amount', 'approved_tenor_months', 'approved_by', 'approved_at']);
                    } catch (DomainException $e) {
                        Notification::make()->title('Gagal')->body($e->getMessage())->danger()->send();
                    }
                }),

            Action::make('reject')
                ->label('Tolak')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->form([
                    Textarea::make('reason')
                        ->label('Alasan Penolakan')
                        ->required()
                        ->rows(3),
                ])
                ->visible(fn (): bool => in_array($this->record->status, [LoanApplicationStatus::Submitted, LoanApplicationStatus::UnderReview]))
                ->action(function (array $data): void {
                    try {
                        app(RejectLoanApplication::class)->execute($this->record, auth()->user(), $data['reason']);
                        Notification::make()->title('Permohonan ditolak')->warning()->send();
                        $this->refreshFormData(['status', 'rejection_reason']);
                    } catch (DomainException $e) {
                        Notification::make()->title('Gagal')->body($e->getMessage())->danger()->send();
                    }
                }),

            Action::make('disburse')
                ->label('Cairkan')
                ->icon('heroicon-o-currency-dollar')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Cairkan Kredit')
                ->modalDescription(function (): string {
                    $amount = number_format((float) $this->record->approved_amount, 0, ',', '.');

                    return "Cairkan kredit sebesar Rp {$amount} untuk tenor {$this->record->approved_tenor_months} bulan?";
                })
                ->visible(fn (): bool => $this->record->status === LoanApplicationStatus::Approved)
                ->action(function (): void {
                    try {
                        $account = app(DisburseLoan::class)->execute(
                            application: $this->record,
                            performer: auth()->user(),
                        );
                        Notification::make()
                            ->title('Kredit berhasil dicairkan')
                            ->body("No. Rekening: {$account->account_number}")
                            ->success()
                            ->send();
                        $this->refreshFormData(['status', 'disbursed_at']);
                    } catch (DomainException $e) {
                        Notification::make()->title('Gagal')->body($e->getMessage())->danger()->send();
                    }
                }),
        ];
    }
}
