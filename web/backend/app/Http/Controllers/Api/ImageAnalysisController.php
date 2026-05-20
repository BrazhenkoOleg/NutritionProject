<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Analysis\StoreImageAnalysisRequest;
use App\Http\Resources\AnalysisResource;
use App\Services\Analysis\AnalysisService;
use App\Services\Analysis\AnalysisServiceException;
use Illuminate\Http\JsonResponse;

class ImageAnalysisController extends ApiController
{
    public function __construct(
        private readonly AnalysisService $analysisService,
    ) {
    }

    public function store(StoreImageAnalysisRequest $request): JsonResponse
    {
        $data = $request->validated();

        try {
            $analysis = $this->analysisService->createFromImage(
                user: $request->user(),
                image: $request->file('image'),
                mealType: $data['meal_type'],
                entryDate: $data['entry_date'],
            );

            return $this->success([
                'message' => 'Analysis created successfully',
                'data' => new AnalysisResource($analysis),
            ], 201);
        } catch (AnalysisServiceException $error) {
            return $this->serviceError($error);
        } catch (\Throwable $error) {
            report($error);

            return $this->error(
                message: 'Analysis creation error',
                userMessage: 'Не удалось создать анализ. Запись не создана.',
                status: 500,
            );
        }
    }
}