<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum SavingsTransactionType: string implements HasColor, HasLabel
{
    case Deposit = 'deposit';
    case Withdrawal = 'withdrawal';
    case InterestCredit = 'interest_credit';
    case AdminFee = 'admin_fee';
    case Tax = 'tax';
    case Transfer = 'transfer';
    case Opening = 'opening';
    case Closing = 'closing';
    case Hold = 'hold';
    case Unhold = 'unhold';

    public function getLabel(): string
    {
        return match ($this) {
            self::Deposit => 'Setoran',
            self::Withdrawal => 'Penarikan',
            self::InterestCredit => 'Bunga',
            self::AdminFee => 'Biaya Admin',
            self::Tax => 'Pajak',
            self::Transfer => 'Transfer',
            self::Opening => 'Pembukaan',
            self::Closing => 'Penutupan',
            self::Hold => 'Pemblokiran',
            self::Unhold => 'Pembukaan Blokir',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Deposit, self::InterestCredit, self::Opening, self::Unhold => 'success',
            self::Withdrawal, self::AdminFee, self::Tax, self::Closing, self::Hold => 'danger',
            self::Transfer => 'info',
        };
    }

    public function isCredit(): bool
    {
        return in_array($this, [self::Deposit, self::InterestCredit, self::Opening, self::Unhold]);
    }

    public function isDebit(): bool
    {
        return in_array($this, [self::Withdrawal, self::AdminFee, self::Tax, self::Closing, self::Hold]);
    }
}
