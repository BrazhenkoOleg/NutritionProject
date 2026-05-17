<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $jsonPath = database_path('seeders/data/nutrition_products_ru_kbju_100g.json');

        if (!File::exists($jsonPath)) {
            throw new \RuntimeException("JSON file not found: {$jsonPath}");
        }

        $jsonContent = File::get($jsonPath);
        $data = json_decode($jsonContent, true);

        if (!isset($data['products']) || !is_array($data['products'])) {
            throw new \RuntimeException('Invalid JSON structure. Expected key: products');
        }

        foreach ($data['products'] as $item) {
            Product::updateOrCreate(
                [
                    'class_name' => $item['class_name'],
                ],
                [
                    'name_ru' => $item['name_ru'],
                    'kcal_per_100g' => $item['kcal_per_100g'],
                    'protein_per_100g' => $item['protein_per_100g'],
                    'fat_per_100g' => $item['fat_per_100g'],
                    'carbs_per_100g' => $item['carbs_per_100g'],
                ]
            );
        }
    }
}