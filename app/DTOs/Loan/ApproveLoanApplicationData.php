<?php

namespace App\DTOs\Loan;

use App\Models\LoanApplication;
use App\Models\User;

readonly class ApproveLoanApplicationData
{
    public function __construct(
        public LoanApplication $application,
        public User $approver,
        public ?float $approvedAmount = null,
        public ?int $approvedTenor = null,
    ) {}
}
