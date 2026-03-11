<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CustomerType: string implements HasLabel
{
    case Individual = 'individual';
    case Corporate = 'corporate';

    public function getLabel(): string
    {
        return match ($this) {
            self::Individual => 'Perorangan',
            self::Corporate => 'Badan Usaha',
        };
    }
}
