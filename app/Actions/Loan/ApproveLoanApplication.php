<?php

namespace App\Actions\Loan;

use App\DTOs\Loan\ApproveLoanApplicationData;
use App\Enums\LoanApplicationStatus;
use App\Exceptions\Loan\InvalidLoanStatusException;
use App\Exceptions\Loan\LoanSelfApprovalException;
use App\Models\LoanApplication;

class ApproveLoanApplication
{
    public function execute(ApproveLoanApplicationData $dto): LoanApplication
    {
        if (! in_array($dto->application->status, [LoanApplicationStatus::Submitted, LoanApplicationStatus::UnderReview])) {
            throw InvalidLoanStatusException::notApprovable($dto->application);
        }

        if ($dto->application->created_by === $dto->approver->id) {
            throw LoanSelfApprovalException::cannotApproveSelf($dto->application, $dto->approver);
        }

        $dto->application->update([
            'status' => LoanApplicationStatus::Approved,
            'approved_amount' => $dto->approvedAmount ?? $dto->application->requested_amount,
            'approved_tenor_months' => $dto->approvedTenor ?? $dto->application->requested_tenor_months,
            'approved_by' => $dto->approver->id,
            'approved_at' => now(),
        ]);

        return $dto->application->fresh();
    }
}
