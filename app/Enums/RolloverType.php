<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum RolloverType: string implements HasLabel
{
    case None = 'none';
    case PrincipalOnly = 'principal_only';
    case PrincipalAndInterest = 'principal_and_interest';

    public function getLabel(): string
    {
        return match ($this) {
            self::None => 'Tidak Diperpanjang',
            self::PrincipalOnly => 'Pokok Saja',
            self::PrincipalAndInterest => 'Pokok + Bunga',
        };
    }
}
