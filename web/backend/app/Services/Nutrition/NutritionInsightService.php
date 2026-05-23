<?php

namespace App\Services\Nutrition;

use App\Models\Analysis;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;

class NutritionInsightService
{
    public function analyzeWeek(User $user, ?string $date = null): array
    {
        $payload = $this->buildPayload($user, $date);

        $response = Http::withHeaders([
            'X-ML-API-Key' => config('services.ml.api_key'),
        ])->timeout(20)->post(
            rtrim(config('services.ml.url'), '/') . '/nutrition/weekly-analysis',
            $payload,
        );

        if ($response->failed()) {
            return $this->fallbackInsight();
        }

        return $response->json();
    }

    private function buildPayload(User $user, ?string $date = null): array
    {
        $currentDate = $date
            ? CarbonImmutable::parse($date)
            : CarbonImmutable::today();

        $startOfWeek = $currentDate->startOfWeek();
        $endOfWeek = $currentDate->endOfWeek();

        $analyses = Analysis::query()
            ->where('user_id', $user->id)
            ->whereBetween('entry_date', [
                $startOfWeek->toDateString(),
                $endOfWeek->toDateString(),
            ])
            ->with('analysisProducts')
            ->get();

        $days = collect(range(0, 6))->map(function (int $dayOffset) use ($startOfWeek, $analyses) {
            $date = $startOfWeek->addDays($dayOffset)->toDateString();

            $dayAnalyses = $analyses->filter(function (Analysis $analysis) use ($date) {
                return $analysis->entry_date->toDateString() === $date;
            });

            $totals = $this->calculateAnalysesTotals($dayAnalyses);

            return [
                'date' => $date,
                'kcal' => $totals['kcal'],
                'protein' => $totals['protein'],
                'fat' => $totals['fat'],
                'carbs' => $totals['carbs'],
            ];
        })->values()->all();

        return [
            'days' => $days,
            'targets' => [
                'kcal' => (float) ($user->daily_kcal_goal ?? 0),
                'protein' => (float) ($user->daily_protein_goal ?? 0),
                'fat' => (float) ($user->daily_fat_goal ?? 0),
                'carbs' => (float) ($user->daily_carbs_goal ?? 0),
            ],
        ];
    }

    private function calculateAnalysesTotals($analyses): array
    {
        return $analyses->reduce(
            function (array $totals, Analysis $analysis) {
                foreach ($analysis->analysisProducts as $product) {
                    $totals['kcal'] += (float) $product->total_kcal;
                    $totals['protein'] += (float) $product->total_protein;
                    $totals['fat'] += (float) $product->total_fat;
                    $totals['carbs'] += (float) $product->total_carbs;
                }

                return $totals;
            },
            [
                'kcal' => 0.0,
                'protein' => 0.0,
                'fat' => 0.0,
                'carbs' => 0.0,
            ],
        );
    }

    private function fallbackInsight(): array
    {
        return [
            'type' => 'unavailable',
            'title' => 'Анализ недели временно недоступен',
            'description' => 'Не удалось получить интеллектуальную рекомендацию по недельному рациону.',
            'recommendations' => [
                'Попробуйте обновить статистику позже.',
            ],
            'features' => [],
        ];
    }
}