<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnalysisResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $products = $this->analysisProducts;

        return [
            'id' => $this->id,
            'meal_type' => $this->meal_type,
            'entry_date' => optional($this->entry_date)->format('Y-m-d'),

            'image_path' => $this->image_path,
            'image_url' => $this->image_url,
            'image_public_id' => $this->image_public_id,

            'status' => $this->status,
            'detections_count' => $this->detections_count,
            'products_count' => $this->products_count,
            'detections' => $this->detections ?? [],

            'products' => AnalysisProductResource::collection($products),

            'totals' => [
                'kcal' => round($products->sum('total_kcal'), 2),
                'protein' => round($products->sum('total_protein'), 2),
                'fat' => round($products->sum('total_fat'), 2),
                'carbs' => round($products->sum('total_carbs'), 2),
            ],

            'note' => $this->note,
            'created_at' => optional($this->created_at)->toISOString(),
            'updated_at' => optional($this->updated_at)->toISOString(),
        ];
    }
}