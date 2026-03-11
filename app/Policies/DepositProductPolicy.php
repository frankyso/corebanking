<?php

namespace App\Policies;

class DepositProductPolicy extends BankingPolicy
{
    protected function module(): string
    {
        return 'deposit-product';
    }
}
