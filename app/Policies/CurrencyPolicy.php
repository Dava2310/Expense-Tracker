<?php

namespace App\Policies;

use App\Models\Currency;
use App\Models\User;

class CurrencyPolicy
{
    /**
     * Any signed-in user can create currencies of their own.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Only the owner can see a currency.
     */
    public function view(User $user, Currency $currency): bool
    {
        return $currency->user_id === $user->id;
    }

    /**
     * Only the owner can change a currency.
     */
    public function update(User $user, Currency $currency): bool
    {
        return $currency->user_id === $user->id;
    }

    /**
     * Only the owner can delete a currency. Whether an account still uses it is checked by the controller.
     */
    public function delete(User $user, Currency $currency): bool
    {
        return $currency->user_id === $user->id;
    }
}
