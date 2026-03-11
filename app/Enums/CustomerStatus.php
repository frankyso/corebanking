<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum CustomerStatus: string implements HasColor, HasLabel
{
    case PendingApproval = 'pending_approval';
    case Active = 'active';
    case Inactive = 'inactive';
    case Blocked = 'blocked';
    case Closed = 'closed';

    public function getLabel(): string
    {
        return match ($this) {
            self::PendingApproval => 'Menunggu Persetujuan',
            self::Active => 'Aktif',
            self::Inactive => 'Tidak Aktif',
            self::Blocked => 'Diblokir',
            self::Closed => 'Ditutup',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PendingApproval => 'warning',
            self::Active => 'success',
            self::Inactive => 'gray',
            self::Blocked => 'danger',
            self::Closed => 'gray',
        };
    }
}
