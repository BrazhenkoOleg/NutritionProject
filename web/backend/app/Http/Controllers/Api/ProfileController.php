<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'gender' => ['required', 'string', 'in:male,female'],
            'age' => ['required', 'integer', 'min:14', 'max:100'],
            'height_cm' => ['required', 'numeric', 'min:120', 'max:230'],
            'weight_kg' => ['required', 'numeric', 'min:35', 'max:250'],
            'activity_level' => ['required', 'string', 'in:sedentary,light,moderate,active,very_active'],
            'goal' => ['required', 'string', 'in:lose,maintain,gain'],
        ]);

        $targets = $this->calculateTargets($data);

        $user = $request->user();

        $user->update([
            ...$data,
            ...$targets,
            'profile_completed' => true,
        ]);

        return response()->json([
            'status' => 'ok',
            'message' => 'Profile updated successfully',
            'user' => $user->fresh(),
        ]);
    }

    private function calculateTargets(array $data): array
    {
        $weight = (float) $data['weight_kg'];
        $height = (float) $data['height_cm'];
        $age = (int) $data['age'];

        $bmr = 10 * $weight + 6.25 * $height - 5 * $age;

        if ($data['gender'] === 'male') {
            $bmr += 5;
        } else {
            $bmr -= 161;
        }

        $activityFactors = [
            'sedentary' => 1.2,
            'light' => 1.375,
            'moderate' => 1.55,
            'active' => 1.725,
            'very_active' => 1.9,
        ];

        $maintenanceCalories = $bmr * $activityFactors[$data['activity_level']];

        if ($data['goal'] === 'lose') {
            $targetCalories = $maintenanceCalories * 0.85;
        } elseif ($data['goal'] === 'gain') {
            $targetCalories = $maintenanceCalories * 1.10;
        } else {
            $targetCalories = $maintenanceCalories;
        }

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
}