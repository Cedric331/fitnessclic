<?php

namespace App\Policies;

use App\Models\Exercise;
use App\Models\User;

class ExercisePolicy
{
    /**
     * Determine if the user can create exercises.
     */
    public function create(User $user): bool
    {
        return $user->isPro();
    }

    /**
     * Determine if the user can update the exercise.
     */
    public function update(User $user, Exercise $exercise): bool
    {
        // L'utilisateur peut modifier ses propres exercices ou être admin
        return ($user->id === $exercise->user_id || $user->isAdmin()) && $user->isPro();
    }

    /**
     * Determine if the user can delete the exercise.
     */
    public function delete(User $user, Exercise $exercise): bool
    {
        // L'utilisateur peut supprimer ses propres exercices ou être admin
        return ($user->id === $exercise->user_id || $user->isAdmin()) && $user->isPro();
    }

    /**
     * Determine if the user can import exercises.
     */
    public function import(User $user): bool
    {
        return $user->isPro();
    }
}
