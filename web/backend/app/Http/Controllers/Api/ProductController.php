<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    public function index(): JsonResponse
    {
        $products = Product::query()
            ->orderBy('name_ru')
            ->get();

        return response()->json([
            'status' => 'ok',
            'data' => $products,
        ]);
    }
}