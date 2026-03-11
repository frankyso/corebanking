<?php

namespace App\Policies;

class HolidayPolicy extends BankingPolicy
{
    protected function module(): string
    {
        return 'holiday';
    }
}
