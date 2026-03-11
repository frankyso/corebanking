<?php

namespace App\Actions\Loan;

use App\Enums\LoanApplicationStatus;
use App\Exceptions\Loan\InvalidLoanStatusException;
use App\Models\LoanApplication;
use App\Models\User;

class RejectLoanApplication
{
    public function execute(LoanApplication $application, User $approver, string $reason): LoanApplication
    {
        if (! in_array($application->status, [LoanApplicationStatus::Submitted, LoanApplicationStatus::UnderReview])) {
            throw InvalidLoanStatusException::notRejectable($application);
        }

        $application->update([
            'status' => LoanApplicationStatus::Rejected,
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'rejection_reason' => $reason,
        ]);

        return $application->fresh();
    }
}
