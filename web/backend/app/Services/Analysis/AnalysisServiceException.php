<?php

namespace App\Services\Analysis;

use RuntimeException;

class AnalysisServiceException extends RuntimeException
{
    public function __construct(
        private readonly string $errorKey,
        private readonly string $userMessage,
        private readonly int $statusCode = 400,
        private readonly array $context = [],
    ) {
        parent::__construct($errorKey);
    }

    public function errorKey(): string
    {
        return $this->errorKey;
    }

    public function userMessage(): string
    {
        return $this->userMessage;
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    public function context(): array
    {
        return $this->context;
    }
}