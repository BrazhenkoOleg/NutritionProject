<?php

namespace App\Services\Profile;

use App\Models\User;

class ProfileService
{
    public function __construct(
        private readonly NutritionTargetService $nutritionTargetService,
    ) {
    }

    public function update(User $user, array $data): User
    {
        $targets = $this->nutritionTargetService->calculate($data);

        $user->update([
            ...$data,
            ...$targets,
            'profile_completed' => true,
        ]);

        return $user->fresh();
    }
}