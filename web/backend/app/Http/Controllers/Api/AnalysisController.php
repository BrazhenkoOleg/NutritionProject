<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Analysis;
use App\Models\Product;
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
            ->latest()
            ->get();

        return response()->json([
            'status' => 'ok',
            'data' => $analyses,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'meal_type' => ['required', 'string', 'in:breakfast,lunch,dinner,snack'],
            'entry_date' => ['required', 'date'],
        ]);

        $file = $request->file('image');

        $path = $file->store('uploads', 'public');
        $imageUrl = asset('storage/' . $path);
        $fullImagePath = storage_path('app/public/' . $path);

        $yoloResponse = Http::attach(
            'image',
            file_get_contents($fullImagePath),
            $file->getClientOriginalName()
        )->post('http://127.0.0.1:8001/predict');

        if (!$yoloResponse->successful()) {
            return response()->json([
                'status' => 'error',
                'message' => 'YOLO API request failed',
                'details' => $yoloResponse->json(),
            ], 500);
        }

        $yoloData = $yoloResponse->json();

        $products = collect($yoloData['products'] ?? [])
            ->map(function ($detectedProduct) {
                $product = Product::where('class_name', $detectedProduct['class_name'])->first();
                $weight = 100;

                return [
                    'class_name' => $detectedProduct['class_name'],
                    'name_ru' => $product?->name_ru,

                    'weight_g' => $weight,

                    'detected_count' => $detectedProduct['count'],
                    'max_confidence' => $detectedProduct['max_confidence'],

                    'kcal_per_100g' => $product?->kcal_per_100g,
                    'protein_per_100g' => $product?->protein_per_100g,
                    'fat_per_100g' => $product?->fat_per_100g,
                    'carbs_per_100g' => $product?->carbs_per_100g,

                    'total_kcal' => round(((float) $product?->kcal_per_100g) * $weight / 100, 2),
                    'total_protein' => round(((float) $product?->protein_per_100g) * $weight / 100, 2),
                    'total_fat' => round(((float) $product?->fat_per_100g) * $weight / 100, 2),
                    'total_carbs' => round(((float) $product?->carbs_per_100g) * $weight / 100, 2),
                ];
            })
            ->values()
            ->all();

        $detections = collect($yoloData['detections'] ?? [])
            ->map(function ($detection) {
                $product = Product::where('class_name', $detection['class_name'])->first();

                return [
                    'class_name' => $detection['class_name'],
                    'name_ru' => $product?->name_ru,
                    'confidence' => $detection['confidence'],
                    'bbox' => $detection['bbox'],
                    'nutrition_per_100g' => [
                        'kcal' => $product?->kcal_per_100g,
                        'protein' => $product?->protein_per_100g,
                        'fat' => $product?->fat_per_100g,
                        'carbs' => $product?->carbs_per_100g,
                    ],
                ];
            })
            ->values()
            ->all();

        $analysis = Analysis::create([
            'user_id' => request()->user()->id,
            'image_path' => $path,
            'image_url' => $imageUrl,
            'status' => 'completed',
            'meal_type' => $data['meal_type'],
            'detections_count' => $yoloData['detections_count'] ?? count($detections),
            'products_count' => $yoloData['products_count'] ?? count($products),
            'detections' => $detections,
            'products' => $products,
            'note' => 'КБЖУ указаны справочно на 100 г продукта. Масса продукта по изображению не рассчитывается.',
            'entry_date' => $data['entry_date'],
        ]);

        return response()->json([
            'status' => 'ok',
            'message' => 'Image analyzed successfully',
            'analysis' => $analysis,
        ]);
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

        $groupedInput = collect($data['products'])
            ->groupBy('class_name')
            ->map(function ($items, string $className) {
                return [
                    'class_name' => $className,
                    'weight_g' => $items->sum('weight_g'),
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

        $products = $groupedInput
            ->map(function ($item) use ($productMap) {
                $product = $productMap->get($item['class_name']);
                $weight = (float) $item['weight_g'];

                return [
                    'class_name' => $item['class_name'],
                    'name_ru' => $product?->name_ru,

                    'weight_g' => round($weight, 1),

                    'kcal_per_100g' => $product?->kcal_per_100g,
                    'protein_per_100g' => $product?->protein_per_100g,
                    'fat_per_100g' => $product?->fat_per_100g,
                    'carbs_per_100g' => $product?->carbs_per_100g,

                    'total_kcal' => round(((float) $product?->kcal_per_100g) * $weight / 100, 2),
                    'total_protein' => round(((float) $product?->protein_per_100g) * $weight / 100, 2),
                    'total_fat' => round(((float) $product?->fat_per_100g) * $weight / 100, 2),
                    'total_carbs' => round(((float) $product?->carbs_per_100g) * $weight / 100, 2),
                ];
            })
            ->values()
            ->all();

        $totals = [
            'kcal' => round(collect($products)->sum('total_kcal'), 2),
            'protein' => round(collect($products)->sum('total_protein'), 2),
            'fat' => round(collect($products)->sum('total_fat'), 2),
            'carbs' => round(collect($products)->sum('total_carbs'), 2),
        ];

        $analysis->update([
            'products' => $products,
            'products_count' => count($products),
            'detections_count' => count($products),
            'status' => 'edited',
            'note' => 'Список продуктов и масса были отредактированы пользователем. КБЖУ рассчитаны на основе указанной массы продукта.',
        ]);

        $freshAnalysis = $analysis->fresh();
        $freshAnalysis->totals = $totals;

        return response()->json([
            'status' => 'ok',
            'message' => 'Analysis products updated successfully',
            'analysis' => $freshAnalysis,
            'totals' => $totals,
        ]);
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

        $groupedInput = collect($data['products'])
            ->groupBy('class_name')
            ->map(function ($items, string $className) {
                return [
                    'class_name' => $className,
                    'weight_g' => $items->sum('weight_g'),
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

        $products = $groupedInput
            ->map(function ($item) use ($productMap) {
                $product = $productMap->get($item['class_name']);
                $weight = (float) $item['weight_g'];

                return [
                    'class_name' => $item['class_name'],
                    'name_ru' => $product?->name_ru,

                    'weight_g' => round($weight, 1),

                    'kcal_per_100g' => $product?->kcal_per_100g,
                    'protein_per_100g' => $product?->protein_per_100g,
                    'fat_per_100g' => $product?->fat_per_100g,
                    'carbs_per_100g' => $product?->carbs_per_100g,

                    'total_kcal' => round(((float) $product?->kcal_per_100g) * $weight / 100, 2),
                    'total_protein' => round(((float) $product?->protein_per_100g) * $weight / 100, 2),
                    'total_fat' => round(((float) $product?->fat_per_100g) * $weight / 100, 2),
                    'total_carbs' => round(((float) $product?->carbs_per_100g) * $weight / 100, 2),
                ];
            })
            ->values()
            ->all();

        $analysis = Analysis::create([
            'user_id' => $request->user()->id,
            'meal_type' => $data['meal_type'],
            'image_path' => null,
            'image_url' => null,
            'status' => 'manual',
            'detections_count' => 0,
            'products_count' => count($products),
            'detections' => [],
            'products' => $products,
            'note' => 'Запись добавлена вручную без изображения. КБЖУ рассчитаны на основе указанной массы продукта.',
            'entry_date' => $data['entry_date'],
        ]);

        return response()->json([
            'status' => 'ok',
            'message' => 'Manual analysis created successfully',
            'analysis' => $analysis,
        ], 201);
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
}