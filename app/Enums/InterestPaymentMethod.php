<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum InterestPaymentMethod: string implements HasLabel
{
    case Maturity = 'maturity';
    case Monthly = 'monthly';
    case Upfront = 'upfront';

    public function getLabel(): string
    {
        return match ($this) {
            self::Maturity => 'Saat Jatuh Tempo',
            self::Monthly => 'Bulanan',
            self::Upfront => 'Di Muka',
        };
    }
}
