<?php

namespace App\Policies;

use App\Models\Session;
use App\Models\User;

class SessionPolicy
{
    /**
     * Determine if the user can save sessions.
     */
    public function save(User $user): bool
    {
        return $user->isPro();
    }

    /**
     * Determine if the user can view the session.
     */
    public function view(User $user, Session $session): bool
    {
        return $user->id === $session->user_id;
    }

    /**
     * Determine if the user can update the session.
     */
    public function update(User $user, Session $session): bool
    {
        return $user->id === $session->user_id;
    }

    /**
     * Determine if the user can delete the session.
     */
    public function delete(User $user, Session $session): bool
    {
        return $user->id === $session->user_id;
    }

    /**
     * Determine if the user can export PDF.
     */
    public function exportPdf(User $user, Session $session): bool
    {
        return $user->id === $session->user_id && $user->isPro();
    }
}
