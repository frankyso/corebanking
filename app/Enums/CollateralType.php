<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CollateralType: string implements HasLabel
{
    case Land = 'land';
    case Building = 'building';
    case Vehicle = 'vehicle';
    case Deposit = 'deposit';
    case Inventory = 'inventory';
    case Machinery = 'machinery';
    case Other = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::Land => 'Tanah',
            self::Building => 'Bangunan',
            self::Vehicle => 'Kendaraan',
            self::Deposit => 'Deposito',
            self::Inventory => 'Persediaan',
            self::Machinery => 'Mesin/Peralatan',
            self::Other => 'Lainnya',
        };
    }
}
