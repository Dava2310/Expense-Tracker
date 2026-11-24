<?php

namespace App\Policies;

use App\Models\Expense;
use App\Models\User;

class ExpensePolicy
{
    /**
     * Any signed-in user can record expenses. The form only accepts their own accounts.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Only the owner of the account the expense was paid from can see it.
     */
    public function view(User $user, Expense $expense): bool
    {
        return $expense->account->user_id === $user->id;
    }

    /**
     * Only the owner of the account the expense was paid from can change it.
     */
    public function update(User $user, Expense $expense): bool
    {
        return $expense->account->user_id === $user->id;
    }

    /**
     * Only the owner of the account the expense was paid from can delete it.
     */
    public function delete(User $user, Expense $expense): bool
    {
        return $expense->account->user_id === $user->id;
    }
}
