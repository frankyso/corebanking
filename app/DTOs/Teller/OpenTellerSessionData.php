<?php

namespace App\DTOs\Teller;

use App\Models\User;
use App\Models\Vault;

readonly class OpenTellerSessionData
{
    public function __construct(
        public User $teller,
        public Vault $vault,
        public float $openingBalance,
    ) {}
}
