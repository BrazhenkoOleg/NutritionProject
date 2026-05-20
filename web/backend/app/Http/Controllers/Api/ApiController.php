<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Analysis\AnalysisServiceException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;

abstract class ApiController extends Controller
{
    use AuthorizesRequests;

    protected function success(array $payload = [], int $status = 200): JsonResponse
    {
        return response()->json(array_merge([
            'status' => 'ok',
        ], $payload), $status);
    }

    protected function error(
        string $message,
        string $userMessage,
        int $status = 400,
        array $context = [],
    ): JsonResponse {
        return response()->json(array_merge([
            'status' => 'error',
            'message' => $message,
            'user_message' => $userMessage,
        ], $context), $status);
    }

    protected function serviceError(AnalysisServiceException $error): JsonResponse
    {
        return $this->error(
            message: $error->errorKey(),
            userMessage: $error->userMessage(),
            status: $error->statusCode(),
            context: $error->context(),
        );
    }
}