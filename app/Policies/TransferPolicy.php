<?php

namespace App\Policies;

use App\Models\User;

class TransferPolicy
{
    /**
     * Any signed-in user can move money between their own accounts. The form only accepts their own accounts.
     */
    public function create(User $user): bool
    {
        return true;
    }
}
