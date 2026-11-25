<?php

namespace App\Policies;

use App\Models\Transfer;
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

    /**
     * Only the owner of the account the money leaves can change a transfer. Both accounts belong to the same user.
     */
    public function update(User $user, Transfer $transfer): bool
    {
        return $transfer->fromAccount->user_id === $user->id;
    }
}
