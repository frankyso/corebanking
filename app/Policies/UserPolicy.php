<?php

namespace App\Policies;

class UserPolicy extends BankingPolicy
{
    protected function module(): string
    {
        return 'user';
    }
}
