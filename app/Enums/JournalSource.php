<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum JournalSource: string implements HasColor, HasLabel
{
    case Manual = 'manual';
    case System = 'system';
    case Teller = 'teller';
    case Interest = 'interest';
    case Fee = 'fee';
    case Eod = 'eod';

    public function getLabel(): string
    {
        return match ($this) {
            self::Manual => 'Manual',
            self::System => 'Sistem',
            self::Teller => 'Teller',
            self::Interest => 'Bunga',
            self::Fee => 'Biaya',
            self::Eod => 'EOD',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Manual => 'primary',
            self::System => 'gray',
            self::Teller => 'info',
            self::Interest => 'success',
            self::Fee => 'warning',
            self::Eod => 'danger',
        };
    }
}
