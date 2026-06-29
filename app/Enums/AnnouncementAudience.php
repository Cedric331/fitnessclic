<?php

namespace App\Enums;

use App\Models\User;
use Filament\Support\Contracts\HasLabel;

enum AnnouncementAudience: string implements HasLabel
{
    /** Visible uniquement par les coachs (et les admins). */
    case COACHES = 'coaches';

    /** Visible par les coachs et les clients. */
    case ALL = 'all';

    /**
     * Human-readable label (French) — also used by Filament.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::COACHES => 'Coachs uniquement',
            self::ALL => 'Coachs et clients',
        };
    }

    /**
     * Whether an announcement with this audience is visible to the given user.
     */
    public function isVisibleTo(User $user): bool
    {
        return $this === self::ALL || ! $user->isClientAccount();
    }
}
