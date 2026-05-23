<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Nutrition\NutritionInsightService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NutritionInsightController extends Controller
{
    public function __construct(
        private readonly NutritionInsightService $nutritionInsightService,
    ) {
    }

    public function show(Request $request): JsonResponse
    {
        $data = $request->validate([
            'date' => ['nullable', 'date'],
        ]);

        $insight = $this->nutritionInsightService->analyzeWeek(
            user: $request->user(),
            date: $data['date'] ?? null,
        );

        return response()->json([
            'status' => 'ok',
            'insight' => $insight,
        ]);
    }
}