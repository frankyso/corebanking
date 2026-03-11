<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum JournalStatus: string implements HasColor, HasLabel
{
    case Draft = 'draft';
    case PendingApproval = 'pending_approval';
    case Posted = 'posted';
    case Reversed = 'reversed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::PendingApproval => 'Menunggu Persetujuan',
            self::Posted => 'Terposting',
            self::Reversed => 'Dibatalkan',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::PendingApproval => 'warning',
            self::Posted => 'success',
            self::Reversed => 'danger',
        };
    }
}
