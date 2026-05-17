<?php

use App\Http\Controllers\Api\AnalysisController;
use App\Http\Controllers\Api\AuthController;
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
    Route::post('/analyze', [AnalysisController::class, 'store']);
    Route::post('/analyses/manual', [AnalysisController::class, 'storeManual']);
    Route::put('/analyses/{analysis}/products', [AnalysisController::class, 'updateProducts']);
    Route::delete('/analyses/{analysis}', [AnalysisController::class, 'destroy']);
});