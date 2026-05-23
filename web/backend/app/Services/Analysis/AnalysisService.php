<?php

namespace App\Services\Analysis;

use App\Enums\AnalysisStatus;
use App\Models\Analysis;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class AnalysisService
{
    public function __construct(
        private readonly MlRecognitionService $mlRecognitionService,
        private readonly CloudinaryImageService $cloudinaryImageService,
        private readonly AnalysisProductService $analysisProductService,
    ) {
    }

    public function createFromImage(User $user, UploadedFile $image, string $mealType, string $entryDate): Analysis
    {
        $mlData = $this->mlRecognitionService->recognize($image);

        $detectedProducts = collect($mlData['products'] ?? [])
            ->filter(fn ($product) => !empty($product['class_name']))
            ->values();

        if ($detectedProducts->isEmpty()) {
            throw new AnalysisServiceException(
                errorKey: 'No products detected',
                userMessage: 'На фото не удалось распознать продукты. Запись не создана.',
                statusCode: 422,
            );
        }

        $cloudinaryUpload = null;

        try {
            $cloudinaryUpload = $this->cloudinaryImageService->upload($image);

            $analysis = Analysis::create([
                'user_id' => $user->id,
                'meal_type' => $mealType,
                'entry_date' => $entryDate,
                'image_url' => $cloudinaryUpload['url'],
                'image_public_id' => $cloudinaryUpload['public_id'],
                'status' => AnalysisStatus::Analyzed->value,
                'detections_count' => (int) ($mlData['detections_count'] ?? 0),
                'products_count' => 0,
                'detections' => $mlData['detections'] ?? [],
            ]);

            $productsForSync = $detectedProducts
                ->map(fn ($product) => [
                    'class_name' => $product['class_name'],
                    'weight_g' => 100,
                    'detected_count' => $product['count'] ?? null,
                    'max_confidence' => $product['max_confidence'] ?? null,
                ])
                ->all();

            $this->analysisProductService->syncProducts($analysis, $productsForSync);

            return $analysis->fresh(['analysisProducts.product']);
        } catch (\Throwable $error) {
            if ($cloudinaryUpload && !empty($cloudinaryUpload['public_id'])) {
                $this->cloudinaryImageService->delete($cloudinaryUpload['public_id']);
            }

            throw $error;
        }
    }

    public function createManual(User $user, string $mealType, string $entryDate, array $products): Analysis
    {
        $analysis = Analysis::create([
            'user_id' => $user->id,
            'meal_type' => $mealType,
            'entry_date' => $entryDate,
            'image_url' => null,
            'image_public_id' => null,
            'status' => AnalysisStatus::Manual->value,
            'detections_count' => 0,
            'products_count' => 0,
            'detections' => [],
        ]);

        $this->analysisProductService->syncProducts($analysis, $products);

        return $analysis->fresh(['analysisProducts.product']);
    }

    public function updateProducts(Analysis $analysis, array $products): Analysis
    {
        $this->analysisProductService->syncProducts($analysis, $products);

        $analysis->update([
            'status' => $analysis->image_url
                ? AnalysisStatus::Edited->value
                : AnalysisStatus::Manual->value,
        ]);

        return $analysis->fresh(['analysisProducts.product']);
    }

    public function delete(Analysis $analysis): void
    {
        if ($analysis->image_public_id) {
            $this->cloudinaryImageService->delete($analysis->image_public_id);
        }

        if ($analysis->image_path && Storage::disk('public')->exists($analysis->image_path)) {
            Storage::disk('public')->delete($analysis->image_path);
        }

        $analysis->delete();
    }
}