<?php

namespace App\Policies;

class ChartOfAccountPolicy extends BankingPolicy
{
    protected function module(): string
    {
        return 'chart-of-account';
    }
}
