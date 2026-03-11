<?php

namespace App\Exceptions\Loan;

use App\Exceptions\DomainException;
use App\Models\LoanAccount;
use App\Models\LoanApplication;

class InvalidLoanStatusException extends DomainException
{
    public static function notApprovable(LoanApplication $application): static
    {
        return (new static('Permohonan tidak dalam status yang dapat disetujui'))
            ->withContext([
                'application_id' => $application->id,
                'status' => $application->status?->value,
            ]);
    }

    public static function notRejectable(LoanApplication $application): static
    {
        return (new static('Permohonan tidak dalam status yang dapat ditolak'))
            ->withContext([
                'application_id' => $application->id,
                'status' => $application->status?->value,
            ]);
    }

    public static function notApproved(LoanApplication $application): static
    {
        return (new static('Permohonan belum disetujui'))
            ->withContext([
                'application_id' => $application->id,
                'status' => $application->status?->value,
            ]);
    }

    public static function notActive(LoanAccount $account): static
    {
        return (new static('Pinjaman tidak dalam status aktif'))
            ->withContext([
                'account_id' => $account->id,
                'status' => $account->status?->value,
            ]);
    }

    public static function invalidPaymentAmount(float $amount): static
    {
        return (new static('Jumlah pembayaran harus lebih dari 0'))
            ->withContext(['amount' => $amount]);
    }
}
