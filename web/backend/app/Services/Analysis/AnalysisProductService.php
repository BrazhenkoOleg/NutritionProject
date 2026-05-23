<?php

namespace App\Services\Analysis;

use App\Models\Analysis;
use App\Models\Product;

class AnalysisProductService
{
    public function syncProducts(Analysis $analysis, array $products): void
    {
        $groupedProducts = collect($products)
            ->filter(fn ($product) => !empty($product['class_name']))
            ->groupBy('class_name')
            ->map(function ($items, $className) {
                return [
                    'class_name' => $className,
                    'weight_g' => $items->sum(fn ($item) => (float) ($item['weight_g'] ?? 100)),
                    'detected_count' => $items->sum(fn ($item) => (int) ($item['detected_count'] ?? $item['count'] ?? 1)),
                    'max_confidence' => $items->max(fn ($item) => (float) ($item['max_confidence'] ?? 0)),
                ];
            })
            ->values();

        $classNames = $groupedProducts->pluck('class_name')->all();

        $productsByClassName = Product::query()
            ->whereIn('class_name', $classNames)
            ->get()
            ->keyBy('class_name');

        $analysis->analysisProducts()->delete();

        foreach ($groupedProducts as $item) {
            $product = $productsByClassName->get($item['class_name']);

            if (!$product) {
                continue;
            }

            $analysis->analysisProducts()->create(
                $this->makeAnalysisProductPayload(
                    product: $product,
                    weight: (float) $item['weight_g'],
                    detectedCount: (int) $item['detected_count'],
                    maxConfidence: (float) $item['max_confidence'],
                )
            );
        }

        $analysis->update([
            'products_count' => $analysis->analysisProducts()->count(),
        ]);
    }

    private function makeAnalysisProductPayload(
        Product $product,
        float $weight,
        int $detectedCount = 1,
        ?float $maxConfidence = null,
    ): array {
        $kcalPer100g = $this->nutrient($product, [
            'kcal_per_100g',
            'kcal_100g',
            'kcal',
            'calories',
        ]);

        $proteinPer100g = $this->nutrient($product, [
            'protein_per_100g',
            'protein_100g',
            'protein',
        ]);

        $fatPer100g = $this->nutrient($product, [
            'fat_per_100g',
            'fat_100g',
            'fat',
        ]);

        $carbsPer100g = $this->nutrient($product, [
            'carbs_per_100g',
            'carbs_100g',
            'carbohydrates',
            'carbs',
        ]);

        return [
            'product_id' => $product->id,

            'weight_g' => round($weight, 1),

            'detected_count' => $detectedCount,
            'max_confidence' => $maxConfidence,

            'kcal_per_100g' => round($kcalPer100g, 2),
            'protein_per_100g' => round($proteinPer100g, 2),
            'fat_per_100g' => round($fatPer100g, 2),
            'carbs_per_100g' => round($carbsPer100g, 2),

            'total_kcal' => $this->calculateTotal($kcalPer100g, $weight),
            'total_protein' => $this->calculateTotal($proteinPer100g, $weight),
            'total_fat' => $this->calculateTotal($fatPer100g, $weight),
            'total_carbs' => $this->calculateTotal($carbsPer100g, $weight),
        ];
    }

    private function calculateTotal(float $valuePer100g, float $weight): float
    {
        return round($valuePer100g * $weight / 100, 2);
    }

    private function nutrient(Product $product, array $fields): float
    {
        foreach ($fields as $field) {
            if ($product->{$field} !== null) {
                return (float) $product->{$field};
            }
        }

        return 0.0;
    }
}