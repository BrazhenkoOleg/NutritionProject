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
    public function index(): JsonResponse
    {
        $analyses = Analysis::query()
            ->where('user_id', request()->user()->id)
            ->with(['analysisProducts.product'])
            ->latest()
            ->get()
            ->map(function (Analysis $analysis) {
                return $this->formatAnalysis($analysis);
            })
            ->values();

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

        $path = $request->file('image')->store('analyses', 'public');
        $imageUrl = Storage::url($path);

        try {
            $response = Http::timeout(180)
                ->attach(
                    'image',
                    file_get_contents($request->file('image')->getRealPath()),
                    $request->file('image')->getClientOriginalName()
                )
                ->post(config('services.ml.url') . '/predict');
        } catch (ConnectionException $error) {
            return response()->json([
                'status' => 'error',
                'message' => 'ML service connection error',
                'ml_url' => config('services.ml.url') . '/predict',
                'details' => $error->getMessage(),
            ], 502);
        }

        if (!$response->successful()) {
            return response()->json([
                'status' => 'error',
                'message' => 'ML service error',
                'ml_url' => config('services.ml.url') . '/predict',
                'ml_status' => $response->status(),
                'ml_body' => $response->body(),
                'ml_json' => $response->json(),
            ], 502);
        }

        $yoloData = $response->json();

        $detections = $yoloData['detections'] ?? [];
        $detectedProducts = $yoloData['products'] ?? [];

        $analysis = Analysis::create([
            'user_id' => $request->user()->id,
            'meal_type' => $data['meal_type'],
            'entry_date' => $data['entry_date'],

            'image_path' => $path,
            'image_url' => $imageUrl,

            'status' => 'completed',

            'detections_count' => $yoloData['detections_count'] ?? count($detections),
            'products_count' => 0,

            'detections' => $detections,

            /*
             * Поле products оставляем пустым.
             * Теперь продукты анализа хранятся в analysis_products.
             */
            'products' => null,

            'note' => 'КБЖУ указаны справочно. Масса продукта по изображению не рассчитывается автоматически. По умолчанию для найденных продуктов указано 100 г.',
        ]);

        $productsForSync = collect($detectedProducts)
            ->map(function (array $product) {
                return [
                    'class_name' => $product['class_name'],
                    'weight_g' => 100,
                    'detected_count' => $product['count'] ?? null,
                    'max_confidence' => $product['max_confidence'] ?? null,
                ];
            })
            ->values()
            ->all();

        $this->syncAnalysisProducts($analysis, $productsForSync);

        $analysis = $analysis->fresh(['analysisProducts.product']);

        return response()->json([
            'status' => 'ok',
            'message' => 'Image analyzed successfully',
            'analysis' => $this->formatAnalysis($analysis),
        ], 201);
    }

    public function storeManual(Request $request): JsonResponse
    {
        $data = $request->validate([
            'meal_type' => ['required', 'string', 'in:breakfast,lunch,dinner,snack'],
            'entry_date' => ['required', 'date'],
            'products' => ['required', 'array', 'min:1'],
            'products.*.class_name' => ['required', 'string', 'exists:products,class_name'],
            'products.*.weight_g' => ['required', 'numeric', 'min:1', 'max:10000'],
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

            'note' => 'Запись добавлена вручную без изображения. КБЖУ рассчитаны на основе указанной массы продукта.',
        ]);

        $this->syncAnalysisProducts($analysis, $data['products']);

        $analysis = $analysis->fresh(['analysisProducts.product']);

        return response()->json([
            'status' => 'ok',
            'message' => 'Manual analysis created successfully',
            'analysis' => $this->formatAnalysis($analysis),
        ], 201);
    }

    public function updateProducts(Request $request, Analysis $analysis): JsonResponse
    {
        if ($analysis->user_id !== $request->user()->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Forbidden',
            ], 403);
        }

        $data = $request->validate([
            'products' => ['required', 'array'],
            'products.*.class_name' => ['required', 'string', 'exists:products,class_name'],
            'products.*.weight_g' => ['required', 'numeric', 'min:1', 'max:10000'],
        ]);

        $this->syncAnalysisProducts($analysis, $data['products']);

        $analysis->update([
            'status' => 'edited',
            'products' => null,
            'note' => 'Список продуктов и масса были отредактированы пользователем. КБЖУ рассчитаны на основе указанной массы продукта.',
        ]);

        $analysis = $analysis->fresh(['analysisProducts.product']);

        $formattedAnalysis = $this->formatAnalysis($analysis);

        return response()->json([
            'status' => 'ok',
            'message' => 'Analysis products updated successfully',
            'analysis' => $formattedAnalysis,
            'totals' => $this->getAnalysisTotalsFromFormattedProducts($formattedAnalysis['products']),
        ]);
    }

    public function destroy(Request $request, Analysis $analysis): JsonResponse
    {
        if ($analysis->user_id !== $request->user()->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Forbidden',
            ], 403);
        }

        if ($analysis->image_path && Storage::disk('public')->exists($analysis->image_path)) {
            Storage::disk('public')->delete($analysis->image_path);
        }

        $analysis->delete();

        return response()->json([
            'status' => 'ok',
            'message' => 'Analysis deleted successfully',
        ]);
    }

    private function syncAnalysisProducts(Analysis $analysis, array $inputProducts): void
    {
        $groupedInput = collect($inputProducts)
            ->filter(function ($item) {
                return !empty($item['class_name']);
            })
            ->groupBy('class_name')
            ->map(function ($items, string $className) {
                return [
                    'class_name' => $className,
                    'weight_g' => $items->sum(function ($item) {
                        return (float) ($item['weight_g'] ?? 100);
                    }),
                    'detected_count' => $items->sum(function ($item) {
                        return (int) ($item['detected_count'] ?? $item['count'] ?? 0);
                    }),
                    'max_confidence' => $items->max(function ($item) {
                        return (float) ($item['max_confidence'] ?? 0);
                    }),
                ];
            })
            ->values();

        $classNames = $groupedInput
            ->pluck('class_name')
            ->all();

        $productMap = Product::query()
            ->whereIn('class_name', $classNames)
            ->get()
            ->keyBy('class_name');

        $analysis->analysisProducts()->delete();

        foreach ($groupedInput as $item) {
            $product = $productMap->get($item['class_name']);

            if (!$product) {
                continue;
            }

            $weight = round((float) $item['weight_g'], 1);

            $kcalPer100g = $product->kcal_per_100g !== null ? (float) $product->kcal_per_100g : 0;
            $proteinPer100g = $product->protein_per_100g !== null ? (float) $product->protein_per_100g : 0;
            $fatPer100g = $product->fat_per_100g !== null ? (float) $product->fat_per_100g : 0;
            $carbsPer100g = $product->carbs_per_100g !== null ? (float) $product->carbs_per_100g : 0;

            $detectedCount = (int) ($item['detected_count'] ?? 0);
            $maxConfidence = (float) ($item['max_confidence'] ?? 0);

            $analysis->analysisProducts()->create([
                'product_id' => $product->id,

                'weight_g' => $weight,

                'detected_count' => $detectedCount > 0 ? $detectedCount : null,
                'max_confidence' => $maxConfidence > 0 ? round($maxConfidence, 4) : null,

                'kcal_per_100g' => $product->kcal_per_100g,
                'protein_per_100g' => $product->protein_per_100g,
                'fat_per_100g' => $product->fat_per_100g,
                'carbs_per_100g' => $product->carbs_per_100g,

                'total_kcal' => round($kcalPer100g * $weight / 100, 2),
                'total_protein' => round($proteinPer100g * $weight / 100, 2),
                'total_fat' => round($fatPer100g * $weight / 100, 2),
                'total_carbs' => round($carbsPer100g * $weight / 100, 2),
            ]);
        }

        $analysis->update([
            'products_count' => $analysis->analysisProducts()->count(),
        ]);
    }

    private function formatAnalysis(Analysis $analysis): array
    {
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

            /*
             * ВАЖНО:
             * frontend по-прежнему получает products,
             * но теперь они собираются из таблицы analysis_products.
             */
            'products' => $analysis->analysisProducts
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
                ->values()
                ->all(),

            'note' => $analysis->note,

            'created_at' => $analysis->created_at,
            'updated_at' => $analysis->updated_at,
        ];
    }

    private function getAnalysisTotalsFromFormattedProducts(array $products): array
    {
        return [
            'kcal' => round(collect($products)->sum('total_kcal'), 2),
            'protein' => round(collect($products)->sum('total_protein'), 2),
            'fat' => round(collect($products)->sum('total_fat'), 2),
            'carbs' => round(collect($products)->sum('total_carbs'), 2),
        ];
    }
}