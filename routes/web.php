<?php

use App\Http\Controllers\AuditSessionPdfController;
use App\Http\Controllers\ItemQrCodeController;
use Filament\Http\Middleware\Authenticate;
use Illuminate\Support\Facades\Route;

Route::middleware(Authenticate::class)
    ->get('/items/{item}/qr.svg', ItemQrCodeController::class)
    ->name('items.qr');

Route::middleware(Authenticate::class)
    ->get('/audit-sessions/{auditSession}/pdf', AuditSessionPdfController::class)
    ->name('audit-sessions.pdf');
