<?php

namespace App\Services\Analysis;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

class MlRecognitionService
{
    public function recognize(UploadedFile $image): array
    {
        try {
            $response = Http::connectTimeout(10)
                ->timeout(180)
                ->attach(
                    'image',
                    file_get_contents($image->getRealPath()),
                    $image->getClientOriginalName()
                )
                ->post(rtrim(config('services.ml.url'), '/') . '/predict');
        } catch (ConnectionException) {
            throw new AnalysisServiceException(
                errorKey: 'ML service connection error',
                userMessage: 'Сервис распознавания временно недоступен. Запись не создана, попробуйте позже.',
                statusCode: 502,
            );
        }

        if ($response->status() === 429) {
            throw new AnalysisServiceException(
                errorKey: 'ML service busy',
                userMessage: 'AI-сервис уже обрабатывает другое изображение. Запись не создана.',
                statusCode: 429,
            );
        }

        if (!$response->successful()) {
            throw new AnalysisServiceException(
                errorKey: 'ML service error',
                userMessage: 'Не удалось распознать продукты на фото. Запись не создана, попробуйте ещё раз.',
                statusCode: 502,
                context: [
                    'ml_status' => $response->status(),
                    'ml_json' => $response->json(),
                ],
            );
        }

        $mlData = $response->json();

        if (!is_array($mlData) || ($mlData['status'] ?? null) !== 'ok') {
            throw new AnalysisServiceException(
                errorKey: 'Invalid ML response',
                userMessage: 'Сервис распознавания вернул некорректный ответ. Запись не создана.',
                statusCode: 502,
            );
        }

        return $mlData;
    }
}