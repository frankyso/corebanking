<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum LoanType: string implements HasLabel
{
    case Kmk = 'kmk';
    case Ki = 'ki';
    case Kk = 'kk';

    public function getLabel(): string
    {
        return match ($this) {
            self::Kmk => 'Kredit Modal Kerja',
            self::Ki => 'Kredit Investasi',
            self::Kk => 'Kredit Konsumsi',
        };
    }
}
