<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;

class ConversationPolicy
{
    /**
     * View / reply: only the two participants.
     */
    public function view(User $user, Conversation $conversation): bool
    {
        return $conversation->isParticipant($user);
    }

    public function reply(User $user, Conversation $conversation): bool
    {
        return $conversation->isParticipant($user);
    }
}
