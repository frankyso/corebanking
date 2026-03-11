<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum TellerTransactionType: string implements HasColor, HasLabel
{
    case SavingsDeposit = 'savings_deposit';
    case SavingsWithdrawal = 'savings_withdrawal';
    case LoanPayment = 'loan_payment';
    case CashRequest = 'cash_request';
    case CashReturn = 'cash_return';
    case Transfer = 'transfer';
    case Other = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::SavingsDeposit => 'Setor Tabungan',
            self::SavingsWithdrawal => 'Tarik Tabungan',
            self::LoanPayment => 'Bayar Angsuran',
            self::CashRequest => 'Permintaan Kas',
            self::CashReturn => 'Pengembalian Kas',
            self::Transfer => 'Transfer',
            self::Other => 'Lainnya',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::SavingsDeposit, self::LoanPayment, self::CashReturn => 'success',
            self::SavingsWithdrawal, self::CashRequest => 'danger',
            self::Transfer => 'info',
            self::Other => 'gray',
        };
    }
}
