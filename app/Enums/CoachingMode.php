<?php

namespace App\Enums;

enum CoachingMode: string
{
    case InPerson = 'in_person';
    case Online = 'online';
    case Both = 'both';

    /**
     * Toutes les valeurs possibles.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Libellé lisible (français).
     */
    public function label(): string
    {
        return match ($this) {
            self::InPerson => 'En présentiel',
            self::Online => 'En visio',
            self::Both => 'Présentiel et visio',
        };
    }
}
