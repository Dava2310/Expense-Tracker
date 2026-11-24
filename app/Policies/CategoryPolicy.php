<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    /**
     * Any signed-in user can create categories of their own.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Only the owner can see a category.
     */
    public function view(User $user, Category $category): bool
    {
        return $category->user_id === $user->id;
    }

    /**
     * Only the owner can change a category.
     */
    public function update(User $user, Category $category): bool
    {
        return $category->user_id === $user->id;
    }

    /**
     * Only the owner can delete a category. Whether it is still in use is checked by the controller.
     */
    public function delete(User $user, Category $category): bool
    {
        return $category->user_id === $user->id;
    }
}
