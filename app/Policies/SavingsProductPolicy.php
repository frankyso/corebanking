<?php

namespace App\Policies;

class SavingsProductPolicy extends BankingPolicy
{
    protected function module(): string
    {
        return 'savings-product';
    }
}
