<?php

namespace App\Policies;

use App\Models\Account;
use App\Models\User;

class AccountPolicy
{
    /**
     * Any signed-in user can open accounts of their own.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Only the owner can see an account.
     */
    public function view(User $user, Account $account): bool
    {
        return $account->user_id === $user->id;
    }

    /**
     * Only the owner can change an account.
     */
    public function update(User $user, Account $account): bool
    {
        return $account->user_id === $user->id;
    }

    /**
     * Only the owner can deactivate an account.
     */
    public function delete(User $user, Account $account): bool
    {
        return $account->user_id === $user->id;
    }
}
