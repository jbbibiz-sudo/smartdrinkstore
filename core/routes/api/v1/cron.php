<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\StockController;

Route::prefix('cron')->middleware('cron.secret')->group(function () {

    Route::get('/check-low-stock', [StockController::class, 'cronCheckLowStock']);
    Route::get('/send-alerts', [StockController::class, 'cronSendAlerts']);
    Route::get('/cleanup-old-movements', [StockController::class, 'cronCleanupMovements']);
});
