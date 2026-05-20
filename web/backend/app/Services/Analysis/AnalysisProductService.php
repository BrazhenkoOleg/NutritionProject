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

            $weight = (float) $item['weight_g'];
            $factor = $weight / 100;

            $analysis->analysisProducts()->create([
                'product_id' => $product->id,
                'class_name' => $product->class_name,
                'weight_g' => $weight,
                'detected_count' => $item['detected_count'],
                'max_confidence' => $item['max_confidence'],
                'total_kcal' => round($this->nutrient($product, ['kcal', 'calories', 'kcal_100g', 'kcal_per_100g']) * $factor, 2),
                'total_protein' => round($this->nutrient($product, ['protein', 'protein_100g', 'protein_per_100g']) * $factor, 2),
                'total_fat' => round($this->nutrient($product, ['fat', 'fat_100g', 'fat_per_100g']) * $factor, 2),
                'total_carbs' => round($this->nutrient($product, ['carbs', 'carbohydrates', 'carbs_100g', 'carbs_per_100g']) * $factor, 2),
            ]);
        }

        $analysis->update([
            'products_count' => $analysis->analysisProducts()->count(),
        ]);
    }

    private function nutrient(Product $product, array $fields): float
    {
        foreach ($fields as $field) {
            if (isset($product->{$field})) {
                return (float) $product->{$field};
            }
        }

        return 0.0;
    }
}