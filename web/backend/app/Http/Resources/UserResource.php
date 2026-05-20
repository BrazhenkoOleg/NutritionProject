<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,

            'gender' => $this->gender,
            'age' => $this->age,
            'height_cm' => $this->height_cm,
            'weight_kg' => $this->weight_kg,
            'activity_level' => $this->activity_level,
            'goal' => $this->goal,

            'daily_kcal_goal' => $this->daily_kcal_goal,
            'daily_protein_goal' => $this->daily_protein_goal,
            'daily_fat_goal' => $this->daily_fat_goal,
            'daily_carbs_goal' => $this->daily_carbs_goal,

            'profile_completed' => (bool) $this->profile_completed,

            'created_at' => optional($this->created_at)->toISOString(),
            'updated_at' => optional($this->updated_at)->toISOString(),
        ];
    }
}