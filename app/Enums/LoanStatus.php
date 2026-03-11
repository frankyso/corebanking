<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum LoanStatus: string implements HasColor, HasLabel
{
    case Active = 'active';
    case Current = 'current';
    case Overdue = 'overdue';
    case Restructured = 'restructured';
    case WrittenOff = 'written_off';
    case Closed = 'closed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Active => 'Aktif',
            self::Current => 'Lancar',
            self::Overdue => 'Menunggak',
            self::Restructured => 'Restrukturisasi',
            self::WrittenOff => 'Hapus Buku',
            self::Closed => 'Lunas',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Active, self::Current => 'success',
            self::Overdue => 'danger',
            self::Restructured => 'warning',
            self::WrittenOff => 'gray',
            self::Closed => 'info',
        };
    }
}
