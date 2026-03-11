<?php

namespace App\Policies;

use App\Models\User;

class LoanAccountPolicy extends BankingPolicy
{
    protected function module(): string
    {
        return 'loan-account';
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, $model): bool
    {
        return false;
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
