<?php

namespace App\DTOs\Loan;

use App\Models\LoanAccount;
use App\Models\User;

readonly class MakeLoanPaymentData
{
    public function __construct(
        public LoanAccount $account,
        public float $amount,
        public User $performer,
        public ?string $description = null,
    ) {}
}
