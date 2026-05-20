<?php

namespace App\Enums;

enum ActivityLevel: string
{
    case Sedentary = 'sedentary';
    case Light = 'light';
    case Moderate = 'moderate';
    case Active = 'active';
    case VeryActive = 'very_active';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}