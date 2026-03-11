<?php

namespace App\Policies;

use App\Models\User;

class VaultPolicy extends BankingPolicy
{
    protected function module(): string
    {
        return 'vault';
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
