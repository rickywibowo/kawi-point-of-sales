<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\MasterDataController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\RecipeController;
use App\Http\Controllers\Api\StockAdjustmentController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware(['auth:sanctum', 'tenant'])->group(function (): void {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::get('/foundation/permissions/reports', fn () => response()->json(['allowed' => true]))
        ->middleware('permission:reports.view');

    Route::get('/master-data', [MasterDataController::class, 'index'])
        ->middleware('permission:inventory.view');
    Route::post('/categories', [CategoryController::class, 'store'])
        ->middleware('permission:inventory.adjust');
    Route::post('/products', [ProductController::class, 'store'])
        ->middleware('permission:inventory.adjust');

    Route::get('/inventory', [InventoryController::class, 'index'])
        ->middleware('permission:inventory.view');
    Route::post('/recipes', [RecipeController::class, 'store'])
        ->middleware('permission:inventory.adjust');
    Route::post('/stock-adjustments', [StockAdjustmentController::class, 'store'])
        ->middleware('permission:inventory.adjust');
});
