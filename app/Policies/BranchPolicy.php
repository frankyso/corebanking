<?php

namespace App\Policies;

class BranchPolicy extends BankingPolicy
{
    protected function module(): string
    {
        return 'branch';
    }
}
