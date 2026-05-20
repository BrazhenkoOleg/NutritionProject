<?php

use App\Http\Controllers\Api\AnalysisController;
use App\Http\Controllers\Api\AnalysisProductController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ImageAnalysisController;
use App\Http\Controllers\Api\ManualAnalysisController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'message' => 'Laravel API is working',
    ]);
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::put('/profile', [ProfileController::class, 'update']);

    Route::get('/products', [ProductController::class, 'index']);

    Route::get('/analyses', [AnalysisController::class, 'index']);
    Route::delete('/analyses/{analysis}', [AnalysisController::class, 'destroy']);

    Route::post('/analyze', [ImageAnalysisController::class, 'store']);
    Route::post('/analyses/manual', [ManualAnalysisController::class, 'store']);
    Route::put('/analyses/{analysis}/products', [AnalysisProductController::class, 'update']);
});