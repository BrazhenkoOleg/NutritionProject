<?php

namespace App\Services\Profile;

use App\Enums\ActivityLevel;
use App\Enums\Gender;
use App\Enums\NutritionGoal;

class NutritionTargetService
{
    public function calculate(array $data): array
    {
        $weight = (float) $data['weight_kg'];
        $height = (float) $data['height_cm'];
        $age = (int) $data['age'];

        $bmr = 10 * $weight + 6.25 * $height - 5 * $age;

        if ($data['gender'] === Gender::Male->value) {
            $bmr += 5;
        } else {
            $bmr -= 161;
        }

        $maintenanceCalories = $bmr * $this->activityFactor($data['activity_level']);
        $targetCalories = $this->applyGoal($maintenanceCalories, $data['goal']);

        $protein = $weight * 1.6;

        $fatCalories = $targetCalories * 0.25;
        $fat = $fatCalories / 9;

        $proteinCalories = $protein * 4;
        $carbsCalories = $targetCalories - $proteinCalories - $fatCalories;
        $carbs = max($carbsCalories / 4, 0);

        return [
            'daily_kcal_goal' => round($targetCalories),
            'daily_protein_goal' => round($protein, 2),
            'daily_fat_goal' => round($fat, 2),
            'daily_carbs_goal' => round($carbs, 2),
        ];
    }

    private function activityFactor(string $activityLevel): float
    {
        return match ($activityLevel) {
            ActivityLevel::Sedentary->value => 1.2,
            ActivityLevel::Light->value => 1.375,
            ActivityLevel::Moderate->value => 1.55,
            ActivityLevel::Active->value => 1.725,
            ActivityLevel::VeryActive->value => 1.9,
            default => 1.2,
        };
    }

    private function applyGoal(float $maintenanceCalories, string $goal): float
    {
        return match ($goal) {
            NutritionGoal::Lose->value => $maintenanceCalories * 0.85,
            NutritionGoal::Gain->value => $maintenanceCalories * 1.10,
            NutritionGoal::Maintain->value => $maintenanceCalories,
            default => $maintenanceCalories,
        };
    }
}