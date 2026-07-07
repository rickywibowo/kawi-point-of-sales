<?php

use App\Http\Controllers\Api\AccountingController;
use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\MeContextController;
use App\Http\Controllers\Api\UserAccessController;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AuthController::class, 'login']);
Route::get('/health', HealthController::class);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/auth/contexts', [AuthController::class, 'contexts']);
    Route::post('/auth/context', [AuthController::class, 'switchContext']);
});

Route::middleware(['auth:sanctum', StartSession::class])->group(function (): void {
    Route::get('/me/context-options', [MeContextController::class, 'options']);
    Route::get('/me/active-context', [MeContextController::class, 'show']);
    Route::post('/me/active-context', [MeContextController::class, 'store']);
    Route::delete('/me/active-context', [MeContextController::class, 'destroy']);
});

Route::middleware(['auth:sanctum', 'tenant'])->group(function (): void {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::get('/user-access', [UserAccessController::class, 'index'])
        ->middleware('permission:users.manage');
    Route::post('/user-access/users', [UserAccessController::class, 'store'])
        ->middleware('permission:users.manage');
    Route::post('/user-access/users/{user}/roles', [UserAccessController::class, 'assignRole'])
        ->middleware('permission:users.manage');
    Route::get('/audit-logs', [AuditLogController::class, 'index'])
        ->middleware('permission:users.manage');

    Route::get('/accounting', [AccountingController::class, 'index'])
        ->middleware('permission:accounting.manage');
    Route::post('/journal-entries', [AccountingController::class, 'store'])
        ->middleware('permission:accounting.manage');
});
