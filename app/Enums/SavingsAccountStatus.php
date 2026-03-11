<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum SavingsAccountStatus: string implements HasColor, HasLabel
{
    case Active = 'active';
    case Dormant = 'dormant';
    case Frozen = 'frozen';
    case Closed = 'closed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Active => 'Aktif',
            self::Dormant => 'Dorman',
            self::Frozen => 'Dibekukan',
            self::Closed => 'Ditutup',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Dormant => 'warning',
            self::Frozen => 'danger',
            self::Closed => 'gray',
        };
    }
}
