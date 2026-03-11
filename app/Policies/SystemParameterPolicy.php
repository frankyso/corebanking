<?php

namespace App\Policies;

use App\Models\User;

class SystemParameterPolicy extends BankingPolicy
{
    protected function module(): string
    {
        return 'system-parameter';
    }

    public function create(User $user): bool
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
