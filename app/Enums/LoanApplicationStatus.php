<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum LoanApplicationStatus: string implements HasColor, HasLabel
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Disbursed = 'disbursed';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'Diajukan',
            self::UnderReview => 'Dalam Review',
            self::Approved => 'Disetujui',
            self::Rejected => 'Ditolak',
            self::Disbursed => 'Dicairkan',
            self::Cancelled => 'Dibatalkan',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Submitted => 'info',
            self::UnderReview => 'warning',
            self::Approved => 'success',
            self::Rejected => 'danger',
            self::Disbursed => 'primary',
            self::Cancelled => 'gray',
        };
    }
}
