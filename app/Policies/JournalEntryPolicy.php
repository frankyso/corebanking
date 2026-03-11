<?php

namespace App\Policies;

use App\Models\User;

class JournalEntryPolicy extends BankingPolicy
{
    protected function module(): string
    {
        return 'journal';
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
