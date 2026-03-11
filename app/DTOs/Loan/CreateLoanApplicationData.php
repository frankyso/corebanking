<?php

namespace App\DTOs\Loan;

use App\Models\LoanProduct;
use App\Models\User;

readonly class CreateLoanApplicationData
{
    public function __construct(
        public LoanProduct $product,
        public int $customerId,
        public int $branchId,
        public float $requestedAmount,
        public int $requestedTenor,
        public string $purpose,
        public User $creator,
        public ?int $loanOfficerId = null,
    ) {}
}
