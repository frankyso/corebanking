<?php

namespace App\Policies;

class LoanProductPolicy extends BankingPolicy
{
    protected function module(): string
    {
        return 'loan-product';
    }
}
