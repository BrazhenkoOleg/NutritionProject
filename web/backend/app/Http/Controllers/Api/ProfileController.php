<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Services\Profile\ProfileService;
use Illuminate\Http\JsonResponse;

class ProfileController extends ApiController
{
    public function __construct(
        private readonly ProfileService $profileService,
    ) {
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $this->profileService->update(
            user: $request->user(),
            data: $request->validated(),
        );

        return $this->success([
            'message' => 'Profile updated successfully',
            'user' => new UserResource($user),
        ]);
    }
}