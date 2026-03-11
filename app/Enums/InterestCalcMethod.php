<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum InterestCalcMethod: string implements HasLabel
{
    case DailyBalance = 'daily_balance';
    case AverageBalance = 'average_balance';
    case LowestBalance = 'lowest_balance';

    public function getLabel(): string
    {
        return match ($this) {
            self::DailyBalance => 'Saldo Harian',
            self::AverageBalance => 'Saldo Rata-rata',
            self::LowestBalance => 'Saldo Terendah',
        };
    }
}
