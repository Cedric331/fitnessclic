<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Conversation;
use App\Models\User;

/**
 * Logique partagée de démarrage d'une conversation client → coach
 * (utilisée par le contrôleur de messagerie et le flux d'inscription/contact).
 */
class ConversationStarter
{
    public function coachBySlug(string $slug): ?User
    {
        return User::query()
            ->where('role', UserRole::COACH)
            ->whereHas('coachProfile', fn ($q) => $q->where('slug', $slug))
            ->first();
    }

    public function startWithCoach(User $client, User $coach): Conversation
    {
        return Conversation::firstOrCreate([
            'coach_id' => $coach->id,
            'client_id' => $client->id,
        ]);
    }
}
