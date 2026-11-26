<?php

namespace App\Policies;

use App\Models\Transfer;
use App\Models\User;

/**
 * Decides who can create, change and delete transfers.
 */
class TransferPolicy
{
    /**
     * Any signed-in user can move money between their own accounts.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Only the owner of the source account can change a transfer.
     */
    public function update(User $user, Transfer $transfer): bool
    {
        return $transfer->fromAccount->user_id === $user->id;
    }

    /**
     * Only the owner of the source account can delete a transfer.
     */
    public function delete(User $user, Transfer $transfer): bool
    {
        return $transfer->fromAccount->user_id === $user->id;
    }
}
