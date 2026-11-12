<?php

namespace App\Policies;

use App\Models\LcisProject;
use App\Models\User;

/**
 * Staff of every side may browse both systems' records: finding the right counterpart needs it.
 */
class LcisProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, LcisProject $lcisProject): bool
    {
        return true;
    }
}
