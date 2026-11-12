<?php

namespace App\Policies;

use App\Models\User;

/**
 * Sync status and manual sync triggers are admin-only.
 */
class SyncRunPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }
}
