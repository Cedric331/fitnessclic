<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    /**
     * Determine if the user can create categories.
     */
    public function create(User $user): bool
    {
        return $user->isPro();
    }

    /**
     * Determine if the user can update the category.
     */
    public function update(User $user, Category $category): bool
    {
        return $user->id === $category->user_id && $user->isPro();
    }

    /**
     * Determine if the user can delete the category.
     */
    public function delete(User $user, Category $category): bool
    {
        return $user->id === $category->user_id && $user->isPro();
    }
}

