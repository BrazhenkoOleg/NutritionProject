<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnalysisProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $product = $this->product;

        return [
            'id' => $this->id,
            'product_id' => $product?->id,
            'class_name' => $this->class_name,
            'name_ru' => $product?->name_ru ?? $this->class_name,
            'weight_g' => (float) $this->weight_g,
            'detected_count' => $this->detected_count,
            'max_confidence' => $this->max_confidence,
            'total_kcal' => (float) $this->total_kcal,
            'total_protein' => (float) $this->total_protein,
            'total_fat' => (float) $this->total_fat,
            'total_carbs' => (float) $this->total_carbs,
        ];
    }
}