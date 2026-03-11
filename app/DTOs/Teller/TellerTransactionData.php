<?php

namespace App\DTOs\Teller;

use App\Models\TellerSession;
use App\Models\User;

readonly class TellerTransactionData
{
    public function __construct(
        public TellerSession $session,
        public float $amount,
        public User $performer,
        public ?string $description = null,
    ) {}
}
