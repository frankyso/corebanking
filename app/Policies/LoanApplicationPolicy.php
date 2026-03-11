<?php

namespace App\Policies;

use App\Models\User;

class LoanApplicationPolicy extends BankingPolicy
{
    protected function module(): string
    {
        return 'loan-application';
    }

    public function delete(User $user, $model): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
