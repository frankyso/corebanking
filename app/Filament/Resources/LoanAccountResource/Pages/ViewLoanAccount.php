<?php

namespace App\Filament\Resources\LoanAccountResource\Pages;

use App\Enums\LoanStatus;
use App\Filament\Resources\LoanAccountResource;
use App\Services\LoanService;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewLoanAccount extends ViewRecord
{
    protected static string $resource = LoanAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('makePayment')
                ->label('Bayar Angsuran')
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->form([
                    TextInput::make('amount')
                        ->label('Jumlah Pembayaran')
                        ->numeric()
                        ->prefix('Rp')
                        ->required()
                        ->minValue(1)
                        ->helperText(function (): string {
                            $nextSchedule = $this->record->getNextUnpaidSchedule();
                            if (! $nextSchedule) {
                                return 'Tidak ada angsuran tersisa';
                            }
                            $amount = number_format((float) $nextSchedule->total_amount, 0, ',', '.');

                            return "Angsuran ke-{$nextSchedule->installment_number}: Rp {$amount}";
                        }),
                    TextInput::make('description')
                        ->label('Keterangan')
                        ->maxLength(255)
                        ->placeholder('Pembayaran angsuran'),
                ])
                ->visible(fn (): bool => in_array($this->record->status, [LoanStatus::Active, LoanStatus::Current, LoanStatus::Overdue]))
                ->action(function (array $data): void {
                    try {
                        $payment = app(LoanService::class)->makePayment(
                            account: $this->record,
                            amount: (float) $data['amount'],
                            performer: auth()->user(),
                            description: $data['description'] ?? null,
                        );
                        Notification::make()
                            ->title('Pembayaran berhasil')
                            ->body("Ref: {$payment->reference_number} | Pokok: Rp ".number_format((float) $payment->principal_portion, 0, ',', '.').' | Bunga: Rp '.number_format((float) $payment->interest_portion, 0, ',', '.'))
                            ->success()
                            ->send();
                        $this->refreshFormData(['outstanding_principal', 'total_principal_paid', 'total_interest_paid', 'last_payment_date', 'status']);
                    } catch (\InvalidArgumentException $e) {
                        Notification::make()->title('Gagal')->body($e->getMessage())->danger()->send();
                    }
                }),

            Action::make('updateDpd')
                ->label('Update DPD')
                ->icon('heroicon-o-clock')
                ->color('warning')
                ->requiresConfirmation()
                ->visible(fn (): bool => in_array($this->record->status, [LoanStatus::Active, LoanStatus::Current, LoanStatus::Overdue]))
                ->action(function (): void {
                    app(LoanService::class)->updateDpd($this->record);
                    app(LoanService::class)->updateCollectibility($this->record);
                    Notification::make()
                        ->title('DPD & Kolektibilitas diperbarui')
                        ->body("DPD: {$this->record->fresh()->dpd} | Kol: {$this->record->fresh()->collectibility->getLabel()}")
                        ->success()
                        ->send();
                    $this->refreshFormData(['dpd', 'collectibility', 'ckpn_amount', 'status']);
                }),
        ];
    }
}
