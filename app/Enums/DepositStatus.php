<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum DepositStatus: string implements HasColor, HasLabel
{
    case Active = 'active';
    case Matured = 'matured';
    case Withdrawn = 'withdrawn';
    case Rolled = 'rolled';
    case Pledged = 'pledged';
    case Closed = 'closed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Active => 'Aktif',
            self::Matured => 'Jatuh Tempo',
            self::Withdrawn => 'Dicairkan',
            self::Rolled => 'Diperpanjang',
            self::Pledged => 'Dijaminkan',
            self::Closed => 'Ditutup',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Matured => 'warning',
            self::Withdrawn => 'gray',
            self::Rolled => 'info',
            self::Pledged => 'danger',
            self::Closed => 'gray',
        };
    }
}
