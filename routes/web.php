<?php

use App\Http\Controllers\ItemQrCodeController;
use Filament\Http\Middleware\Authenticate;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(Authenticate::class)
    ->get('/admin/items/{item}/qr.svg', ItemQrCodeController::class)
    ->name('items.qr');
