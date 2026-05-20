<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Analysis\StoreManualAnalysisRequest;
use App\Http\Resources\AnalysisResource;
use App\Services\Analysis\AnalysisService;
use App\Services\Analysis\AnalysisServiceException;
use Illuminate\Http\JsonResponse;

class ManualAnalysisController extends ApiController
{
    public function __construct(
        private readonly AnalysisService $analysisService,
    ) {
    }

    public function store(StoreManualAnalysisRequest $request): JsonResponse
    {
        $data = $request->validated();

        try {
            $analysis = $this->analysisService->createManual(
                user: $request->user(),
                mealType: $data['meal_type'],
                entryDate: $data['entry_date'],
                products: $data['products'],
            );

            return $this->success([
                'message' => 'Manual analysis created successfully',
                'data' => new AnalysisResource($analysis),
            ], 201);
        } catch (AnalysisServiceException $error) {
            return $this->serviceError($error);
        } catch (\Throwable $error) {
            report($error);

            return $this->error(
                message: 'Manual analysis creation error',
                userMessage: 'Не удалось создать ручную запись.',
                status: 500,
            );
        }
    }
}