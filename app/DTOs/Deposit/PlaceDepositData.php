<?php

namespace App\DTOs\Deposit;

use App\Enums\InterestPaymentMethod;
use App\Enums\RolloverType;
use App\Models\DepositProduct;
use App\Models\User;
use Carbon\Carbon;

readonly class PlaceDepositData
{
    public function __construct(
        public DepositProduct $product,
        public int $customerId,
        public int $branchId,
        public float $principalAmount,
        public int $tenorMonths,
        public InterestPaymentMethod $interestPaymentMethod,
        public RolloverType $rolloverType,
        public ?int $savingsAccountId,
        public User $performer,
        public ?Carbon $placementDate = null,
    ) {}
}
