<?php

namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'admin';
    case COACH = 'coach';
    case CLIENT = 'client';

    /**
     * Get all role values as an array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get all role names as an array
     */
    public static function names(): array
    {
        return array_column(self::cases(), 'name');
    }

    /**
     * Human-readable label (French)
     */
    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Administrateur',
            self::COACH => 'Coach',
            self::CLIENT => 'Client',
        };
    }
}
