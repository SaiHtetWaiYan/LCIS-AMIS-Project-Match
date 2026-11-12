<?php

namespace App\Policies;

use App\Models\User;

/**
 * User management is admin-only. An admin cannot deactivate or demote themselves,
 * which guarantees at least one active admin always remains.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, User $model): bool
    {
        return $user->isAdmin();
    }

    public function changeSide(User $user, User $model): bool
    {
        return $user->isAdmin() && $user->isNot($model);
    }

    public function deactivate(User $user, User $model): bool
    {
        return $user->isAdmin() && $user->isNot($model) && $model->isActive();
    }

    public function reactivate(User $user, User $model): bool
    {
        return $user->isAdmin() && ! $model->isActive();
    }
}
