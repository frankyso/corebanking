<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum VaultTransactionType: string implements HasColor, HasLabel
{
    case InitialCash = 'initial_cash';
    case CashIn = 'cash_in';
    case CashOut = 'cash_out';
    case TellerRequest = 'teller_request';
    case TellerReturn = 'teller_return';
    case Adjustment = 'adjustment';

    public function getLabel(): string
    {
        return match ($this) {
            self::InitialCash => 'Saldo Awal',
            self::CashIn => 'Kas Masuk',
            self::CashOut => 'Kas Keluar',
            self::TellerRequest => 'Permintaan Teller',
            self::TellerReturn => 'Pengembalian Teller',
            self::Adjustment => 'Penyesuaian',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::InitialCash => 'gray',
            self::CashIn, self::TellerReturn => 'success',
            self::CashOut, self::TellerRequest => 'danger',
            self::Adjustment => 'warning',
        };
    }
}
