<?php

namespace App\Policies;

use App\Models\Analysis;
use App\Models\User;

class AnalysisPolicy
{
    public function view(User $user, Analysis $analysis): bool
    {
        return (int) $analysis->user_id === (int) $user->id;
    }

    public function update(User $user, Analysis $analysis): bool
    {
        return (int) $analysis->user_id === (int) $user->id;
    }

    public function delete(User $user, Analysis $analysis): bool
    {
        return (int) $analysis->user_id === (int) $user->id;
    }
}