<?php

namespace App\Policies;

use App\Models\Income;
use App\Models\User;

class IncomePolicy
{
    /**
     * Any signed-in user can record incomes. The form only accepts their own accounts.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Only the owner of the account the income was received into can see it.
     */
    public function view(User $user, Income $income): bool
    {
        return $income->account->user_id === $user->id;
    }

    /**
     * Only the owner of the account the income was received into can change it.
     */
    public function update(User $user, Income $income): bool
    {
        return $income->account->user_id === $user->id;
    }

    /**
     * Only the owner of the account the income was received into can delete it.
     */
    public function delete(User $user, Income $income): bool
    {
        return $income->account->user_id === $user->id;
    }
}
