<?php

namespace App\Policies;

class CustomerPolicy extends BankingPolicy
{
    protected function module(): string
    {
        return 'customer';
    }
}
