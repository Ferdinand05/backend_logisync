<?php

use App\Http\Controllers\Api\AuditSessionController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TransactionController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
});

Route::middleware('auth:sanctum')->prefix('audit-sessions')->group(function (): void {
    Route::post('/', [AuditSessionController::class, 'store']);
    Route::get('/{auditSession}', [AuditSessionController::class, 'show']);
    Route::post('/{auditSession}/scan', [AuditSessionController::class, 'scan']);
    Route::post('/{auditSession}/complete', [AuditSessionController::class, 'complete']);
});

Route::middleware('auth:sanctum')->prefix('transactions')->group(function (): void {
    Route::get('/', [TransactionController::class, 'index']);
    Route::post('/in', [TransactionController::class, 'in']);
    Route::post('/out', [TransactionController::class, 'out']);
});
