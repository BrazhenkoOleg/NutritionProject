<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Analysis\UpdateAnalysisProductsRequest;
use App\Http\Resources\AnalysisResource;
use App\Models\Analysis;
use App\Services\Analysis\AnalysisService;
use App\Services\Analysis\AnalysisServiceException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;

class AnalysisProductController extends ApiController
{
    public function __construct(
        private readonly AnalysisService $analysisService,
    ) {
    }

    public function update(UpdateAnalysisProductsRequest $request, Analysis $analysis): JsonResponse
    {
        try {
            $this->authorize('update', $analysis);

            $data = $request->validated();

            $updatedAnalysis = $this->analysisService->updateProducts(
                analysis: $analysis,
                products: $data['products'],
            );

            return $this->success([
                'message' => 'Analysis products updated successfully',
                'data' => new AnalysisResource($updatedAnalysis),
            ]);
        } catch (AuthorizationException) {
            return $this->error(
                message: 'Analysis not found',
                userMessage: 'Запись не найдена.',
                status: 404,
            );
        } catch (AnalysisServiceException $error) {
            return $this->serviceError($error);
        } catch (\Throwable $error) {
            report($error);

            return $this->error(
                message: 'Analysis products update error',
                userMessage: 'Не удалось обновить продукты записи.',
                status: 500,
            );
        }
    }
}