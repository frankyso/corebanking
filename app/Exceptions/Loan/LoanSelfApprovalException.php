<?php

namespace App\Exceptions\Loan;

use App\Exceptions\DomainException;
use App\Models\LoanApplication;
use App\Models\User;

class LoanSelfApprovalException extends DomainException
{
    public static function cannotApproveSelf(LoanApplication $application, User $user): static
    {
        return (new static('Tidak dapat menyetujui permohonan yang Anda buat sendiri'))
            ->withContext([
                'application_id' => $application->id,
                'user_id' => $user->id,
            ]);
    }
}
