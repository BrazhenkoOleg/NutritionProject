<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Analysis;
use App\Models\Product;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class AnalysisController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $analyses = Analysis::query()
            ->where('user_id', $request->user()->id)
            ->with(['analysisProducts.product'])
            ->orderByDesc('entry_date')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Analysis $analysis) => $this->formatAnalysis($analysis));

        return response()->json([
            'status' => 'ok',
            'data' => $analyses,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'meal_type' => ['required', 'string', 'in:breakfast,lunch,dinner,snack'],
            'entry_date' => ['required', 'date'],
        ]);

        $path = null;

        try {
            $path = $request->file('image')->store('analyses', 'public');
            $imageUrl = Storage::disk('public')->url($path);

            try {
                $response = Http::connectTimeout(10)
                    ->timeout(180)
                    ->attach(
                        'image',
                        file_get_contents($request->file('image')->getRealPath()),
                        $request->file('image')->getClientOriginalName()
                    )
                    ->post(config('services.ml.url') . '/predict');
            } catch (ConnectionException $error) {
                $this->deleteStoredImage($path);

                return response()->json([
                    'status' => 'error',
                    'message' => 'ML service connection error',
                    'user_message' => 'Сервис распознавания запускается или временно недоступен. Повторите попытку через минуту.',
                    'details' => $error->getMessage(),
                ], 502);
            }

            if ($response->status() === 429) {
                $this->deleteStoredImage($path);

                return response()->json([
                    'status' => 'error',
                    'message' => 'ML service busy',
                    'user_message' => 'AI-сервис уже анализирует изображение. Попробуйте ещё раз через несколько секунд.',
                ], 429);
            }

            if (!$response->successful()) {
                $this->deleteStoredImage($path);

                return response()->json([
                    'status' => 'error',
                    'message' => 'ML service error',
                    'user_message' => 'Сервис распознавания временно недоступен. Попробуйте повторить анализ через несколько секунд.',
                    'ml_status' => $response->status(),
                    'ml_body' => strip_tags($response->body()),
                    'ml_json' => $response->json(),
                ], 502);
            }

            $mlData = $response->json();

            if (!is_array($mlData)) {
                $this->deleteStoredImage($path);

                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid ML response',
                    'user_message' => 'Сервис распознавания вернул некорректный ответ. Попробуйте ещё раз.',
                ], 502);
            }

            $analysis = Analysis::create([
                'user_id' => $request->user()->id,
                'meal_type' => $data['meal_type'],
                'entry_date' => $data['entry_date'],
                'image_path' => $path,
                'image_url' => $imageUrl,
                'status' => 'analyzed',
                'detections_count' => (int) ($mlData['detections_count'] ?? 0),
                'products_count' => 0,
                'detections' => $mlData['detections'] ?? [],
                'products' => null,
                'note' => null,
            ]);

            $productsForSync = collect($mlData['products'] ?? [])
                ->filter(fn ($product) => !empty($product['class_name']))
                ->map(fn ($product) => [
                    'class_name' => $product['class_name'],
                    'weight_g' => 100,
                    'detected_count' => $product['count'] ?? null,
                    'max_confidence' => $product['max_confidence'] ?? null,
                ])
                ->values()
                ->all();

            $this->syncAnalysisProducts($analysis, $productsForSync);

            $analysis->load(['analysisProducts.product']);

            return response()->json([
                'status' => 'ok',
                'message' => 'Analysis created successfully',
                'data' => $this->formatAnalysis($analysis),
            ], 201);
        } catch (\Throwable $error) {
            if ($path) {
                $this->deleteStoredImage($path);
            }

            report($error);

            return response()->json([
                'status' => 'error',
                'message' => 'Analysis creation error',
                'user_message' => 'Не удалось создать анализ. Попробуйте ещё раз.',
            ], 500);
        }
    }

    public function storeManual(Request $request): JsonResponse
    {
        $data = $request->validate([
            'meal_type' => ['required', 'string', 'in:breakfast,lunch,dinner,snack'],
            'entry_date' => ['required', 'date'],
            'products' => ['required', 'array', 'min:1'],
            'products.*.class_name' => ['required', 'string', 'exists:products,class_name'],
            'products.*.weight_g' => ['required', 'numeric', 'min:1', 'max:5000'],
        ]);

        $analysis = Analysis::create([
            'user_id' => $request->user()->id,
            'meal_type' => $data['meal_type'],
            'entry_date' => $data['entry_date'],
            'image_path' => null,
            'image_url' => null,
            'status' => 'manual',
            'detections_count' => 0,
            'products_count' => 0,
            'detections' => [],
            'products' => null,
            'note' => 'Добавлено вручную',
        ]);

        $this->syncAnalysisProducts($analysis, $data['products']);

        $analysis->load(['analysisProducts.product']);

        return response()->json([
            'status' => 'ok',
            'message' => 'Manual analysis created successfully',
            'data' => $this->formatAnalysis($analysis),
        ], 201);
    }

    public function updateProducts(Request $request, Analysis $analysis): JsonResponse
    {
        if ((int) $analysis->user_id !== (int) $request->user()->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Forbidden',
            ], 403);
        }

        $data = $request->validate([
            'products' => ['required', 'array', 'min:1'],
            'products.*.class_name' => ['required', 'string', 'exists:products,class_name'],
            'products.*.weight_g' => ['required', 'numeric', 'min:1', 'max:5000'],
        ]);

        $this->syncAnalysisProducts($analysis, $data['products']);

        $analysis->update([
            'status' => $analysis->image_path ? 'edited' : 'manual',
            'products' => null,
            'note' => $analysis->image_path
                ? 'Порции продуктов уточнены пользователем'
                : 'Ручная запись обновлена',
        ]);

        $analysis->load(['analysisProducts.product']);

        return response()->json([
            'status' => 'ok',
            'message' => 'Analysis products updated successfully',
            'data' => $this->formatAnalysis($analysis),
        ]);
    }

    public function destroy(Request $request, Analysis $analysis): JsonResponse
    {
        if ((int) $analysis->user_id !== (int) $request->user()->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Forbidden',
            ], 403);
        }

        if ($analysis->image_path) {
            $this->deleteStoredImage($analysis->image_path);
        }

        $analysis->delete();

        return response()->json([
            'status' => 'ok',
            'message' => 'Analysis deleted successfully',
        ]);
    }

    private function syncAnalysisProducts(Analysis $analysis, array $products): void
    {
        $groupedProducts = collect($products)
            ->filter(fn ($item) => !empty($item['class_name']))
            ->groupBy('class_name')
            ->map(function ($items, string $className) {
                return [
                    'class_name' => $className,
                    'weight_g' => $items->sum(fn ($item) => (float) ($item['weight_g'] ?? 100)),
                    'detected_count' => $items->sum(fn ($item) => (int) ($item['detected_count'] ?? 0)) ?: null,
                    'max_confidence' => $items->max(fn ($item) => isset($item['max_confidence'])
                        ? (float) $item['max_confidence']
                        : null
                    ),
                ];
            })
            ->values();

        $productModels = Product::query()
            ->whereIn('class_name', $groupedProducts->pluck('class_name'))
            ->get()
            ->keyBy('class_name');

        $analysis->analysisProducts()->delete();

        foreach ($groupedProducts as $item) {
            $product = $productModels->get($item['class_name']);

            if (!$product) {
                continue;
            }

            $weight = (float) ($item['weight_g'] ?? 100);
            $factor = $weight / 100;

            $kcalPer100g = (float) ($product->kcal_per_100g ?? 0);
            $proteinPer100g = (float) ($product->protein_per_100g ?? 0);
            $fatPer100g = (float) ($product->fat_per_100g ?? 0);
            $carbsPer100g = (float) ($product->carbs_per_100g ?? 0);

            $analysis->analysisProducts()->create([
                'product_id' => $product->id,

                'weight_g' => round($weight, 1),

                'detected_count' => $item['detected_count'],
                'max_confidence' => $item['max_confidence'] !== null
                    ? round((float) $item['max_confidence'], 4)
                    : null,

                'kcal_per_100g' => $kcalPer100g,
                'protein_per_100g' => $proteinPer100g,
                'fat_per_100g' => $fatPer100g,
                'carbs_per_100g' => $carbsPer100g,

                'total_kcal' => round($kcalPer100g * $factor, 2),
                'total_protein' => round($proteinPer100g * $factor, 2),
                'total_fat' => round($fatPer100g * $factor, 2),
                'total_carbs' => round($carbsPer100g * $factor, 2),
            ]);
        }

        $analysis->update([
            'products_count' => $analysis->analysisProducts()->count(),
        ]);
    }

    private function formatAnalysis(Analysis $analysis): array
    {
        $products = $analysis->analysisProducts
            ->map(function ($item) {
                return [
                    'class_name' => $item->product?->class_name,
                    'name_ru' => $item->product?->name_ru,

                    'weight_g' => (float) $item->weight_g,

                    'detected_count' => $item->detected_count,
                    'max_confidence' => $item->max_confidence !== null
                        ? (float) $item->max_confidence
                        : null,

                    'kcal_per_100g' => $item->kcal_per_100g !== null
                        ? (float) $item->kcal_per_100g
                        : null,
                    'protein_per_100g' => $item->protein_per_100g !== null
                        ? (float) $item->protein_per_100g
                        : null,
                    'fat_per_100g' => $item->fat_per_100g !== null
                        ? (float) $item->fat_per_100g
                        : null,
                    'carbs_per_100g' => $item->carbs_per_100g !== null
                        ? (float) $item->carbs_per_100g
                        : null,

                    'total_kcal' => (float) $item->total_kcal,
                    'total_protein' => (float) $item->total_protein,
                    'total_fat' => (float) $item->total_fat,
                    'total_carbs' => (float) $item->total_carbs,
                ];
            })
            ->values();

        $totals = $this->getAnalysisTotalsFromProducts($products->all());

        return [
            'id' => $analysis->id,

            'user_id' => $analysis->user_id,

            'meal_type' => $analysis->meal_type,
            'entry_date' => $analysis->entry_date?->format('Y-m-d'),

            'image_path' => $analysis->image_path,
            'image_url' => $analysis->image_url,

            'status' => $analysis->status,

            'detections_count' => $analysis->detections_count,
            'products_count' => $analysis->products_count,

            'detections' => $analysis->detections ?? [],
            'products' => $products,

            'totals' => $totals,

            'note' => $analysis->note,

            'created_at' => $analysis->created_at?->toISOString(),
            'updated_at' => $analysis->updated_at?->toISOString(),
        ];
    }

    private function getAnalysisTotalsFromProducts(array $products): array
    {
        return collect($products)->reduce(
            function (array $acc, array $product) {
                $acc['kcal'] += (float) ($product['total_kcal'] ?? 0);
                $acc['protein'] += (float) ($product['total_protein'] ?? 0);
                $acc['fat'] += (float) ($product['total_fat'] ?? 0);
                $acc['carbs'] += (float) ($product['total_carbs'] ?? 0);

                return $acc;
            },
            [
                'kcal' => 0,
                'protein' => 0,
                'fat' => 0,
                'carbs' => 0,
            ]
        );
    }

    private function deleteStoredImage(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}