<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

abstract class BankingPolicy
{
    use HandlesAuthorization;

    /**
     * Permission module prefix used in Spatie permissions.
     * e.g., 'branch', 'customer', 'savings-account'
     */
    abstract protected function module(): string;

    public function viewAny(User $user): bool
    {
        return $user->can("{$this->module()}.view");
    }

    public function view(User $user, $model): bool
    {
        return $user->can("{$this->module()}.view");
    }

    public function create(User $user): bool
    {
        return $user->can("{$this->module()}.create");
    }

    public function update(User $user, $model): bool
    {
        return $user->can("{$this->module()}.update");
    }

    public function delete(User $user, $model): bool
    {
        return $user->can("{$this->module()}.delete");
    }

    public function deleteAny(User $user): bool
    {
        return $user->can("{$this->module()}.delete");
    }
}
