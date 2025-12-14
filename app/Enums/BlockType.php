<?php

namespace App\Enums;

enum BlockType: string
{
    case STANDARD = 'standard';
    case SET = 'set';

    /**
     * Get all enum values as an array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get the label for display
     */
    public function label(): string
    {
        return match ($this) {
            self::STANDARD => 'Standard',
            self::SET => 'Super Set',
        };
    }
}
