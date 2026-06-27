<?php

namespace App\Policies;

use App\Models\CoachProfile;
use App\Models\User;

class CoachProfilePolicy
{
    /**
     * Only the owning coach may update their profile.
     */
    public function update(User $user, CoachProfile $profile): bool
    {
        return $user->id === $profile->user_id && $user->isCoach();
    }
}
