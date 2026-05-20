<?php

namespace App\Enums;

enum MealType: string
{
    case Breakfast = 'breakfast';
    case Lunch = 'lunch';
    case Dinner = 'dinner';
    case Snack = 'snack';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}