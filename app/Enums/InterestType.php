<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum InterestType: string implements HasLabel
{
    case Flat = 'flat';
    case Effective = 'effective';
    case Annuity = 'annuity';

    public function getLabel(): string
    {
        return match ($this) {
            self::Flat => 'Flat',
            self::Effective => 'Efektif',
            self::Annuity => 'Anuitas',
        };
    }
}
