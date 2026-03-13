<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum TransferType: string implements HasColor, HasLabel
{
    case OwnAccount = 'own_account';
    case InternalTransfer = 'internal_transfer';

    public function getLabel(): string
    {
        return match ($this) {
            self::OwnAccount => 'Transfer Antar Rekening',
            self::InternalTransfer => 'Transfer Internal',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::OwnAccount => 'info',
            self::InternalTransfer => 'primary',
        };
    }
}
