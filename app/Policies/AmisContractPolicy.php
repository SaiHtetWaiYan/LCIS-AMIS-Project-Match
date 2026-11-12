<?php

namespace App\Policies;

use App\Models\AmisContract;
use App\Models\User;

/**
 * Staff of every side may browse both systems' records: finding the right counterpart needs it.
 */
class AmisContractPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, AmisContract $amisContract): bool
    {
        return true;
    }
}
