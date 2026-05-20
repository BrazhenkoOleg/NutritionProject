<?php

namespace App\Enums;

enum NutritionGoal: string
{
    case Lose = 'lose';
    case Maintain = 'maintain';
    case Gain = 'gain';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}