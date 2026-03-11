<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum Collectibility: int implements HasColor, HasLabel
{
    case Current = 1;
    case SpecialMention = 2;
    case Substandard = 3;
    case Doubtful = 4;
    case Loss = 5;

    public function getLabel(): string
    {
        return match ($this) {
            self::Current => '1 - Lancar',
            self::SpecialMention => '2 - Dalam Perhatian Khusus',
            self::Substandard => '3 - Kurang Lancar',
            self::Doubtful => '4 - Diragukan',
            self::Loss => '5 - Macet',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Current => 'success',
            self::SpecialMention => 'warning',
            self::Substandard => 'orange',
            self::Doubtful => 'danger',
            self::Loss => 'gray',
        };
    }

    public function ckpnRate(): float
    {
        return match ($this) {
            self::Current => 0.01,
            self::SpecialMention => 0.05,
            self::Substandard => 0.15,
            self::Doubtful => 0.50,
            self::Loss => 1.00,
        };
    }
}
