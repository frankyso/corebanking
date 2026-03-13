<?php

namespace App\DTOs\MobileBanking;

use App\Models\MobileUser;
use App\Models\SavingsAccount;

readonly class TransferData
{
    public function __construct(
        public SavingsAccount $sourceAccount,
        public SavingsAccount $destinationAccount,
        public float $amount,
        public MobileUser $performer,
        public ?string $description = null,
    ) {}
}
