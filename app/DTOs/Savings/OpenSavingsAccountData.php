<?php

namespace App\DTOs\Savings;

use App\Models\SavingsProduct;
use App\Models\User;

readonly class OpenSavingsAccountData
{
    public function __construct(
        public SavingsProduct $product,
        public int $customerId,
        public int $branchId,
        public float $initialDeposit,
        public User $performer,
    ) {}
}
