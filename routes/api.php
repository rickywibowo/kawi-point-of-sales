<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AccountingController;
use App\Http\Controllers\Api\CashierShiftController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\HeldTransactionController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\MasterDataController;
use App\Http\Controllers\Api\OfflineSyncController;
use App\Http\Controllers\Api\PosController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\GoodsReceiptController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\PurchaseOrderController;
use App\Http\Controllers\Api\PurchasingController;
use App\Http\Controllers\Api\RecipeController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Api\StockAdjustmentController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AuthController::class, 'login']);
Route::get('/health', HealthController::class);

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
    Route::get('/customers', [CustomerController::class, 'index'])
        ->middleware('permission:sales.create');
    Route::post('/customers', [CustomerController::class, 'store'])
        ->middleware('permission:sales.create');
    Route::get('/customers/{customer}', [CustomerController::class, 'show'])
        ->middleware('permission:sales.create');
    Route::patch('/customers/{customer}', [CustomerController::class, 'update'])
        ->middleware('permission:sales.create');

    Route::get('/inventory', [InventoryController::class, 'index'])
        ->middleware('permission:inventory.view');
    Route::post('/recipes', [RecipeController::class, 'store'])
        ->middleware('permission:inventory.adjust');
    Route::post('/stock-adjustments', [StockAdjustmentController::class, 'store'])
        ->middleware('permission:inventory.adjust');

    Route::get('/pos', [PosController::class, 'index'])
        ->middleware('permission:sales.create');
    Route::post('/cashier-shifts', [CashierShiftController::class, 'store'])
        ->middleware('permission:sales.create');
    Route::post('/cashier-shifts/{shift}/close', [CashierShiftController::class, 'close'])
        ->middleware('permission:sales.create');
    Route::post('/sales', [SaleController::class, 'store'])
        ->middleware('permission:sales.create');
    Route::post('/held-transactions', [HeldTransactionController::class, 'store'])
        ->middleware('permission:sales.create');

    Route::get('/purchasing', [PurchasingController::class, 'index'])
        ->middleware('permission:purchases.manage');
    Route::post('/purchase-orders', [PurchaseOrderController::class, 'store'])
        ->middleware('permission:purchases.manage');
    Route::post('/purchase-orders/{purchaseOrder}/approve', [PurchaseOrderController::class, 'approve'])
        ->middleware('permission:purchases.manage');
    Route::post('/goods-receipts', [GoodsReceiptController::class, 'store'])
        ->middleware('permission:purchases.manage');

    Route::get('/accounting', [AccountingController::class, 'index'])
        ->middleware('permission:accounting.manage');
    Route::post('/journal-entries', [AccountingController::class, 'store'])
        ->middleware('permission:accounting.manage');

    Route::post('/offline/sales/sync', [OfflineSyncController::class, 'syncSales'])
        ->middleware('permission:sales.create');
    Route::get('/offline/conflicts', [OfflineSyncController::class, 'conflicts'])
        ->middleware('permission:sales.create');

    Route::get('/reports', [ReportController::class, 'index'])
        ->middleware('permission:reports.view');
});
