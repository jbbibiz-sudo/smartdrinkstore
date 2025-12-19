<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\StockController;

Route::prefix('stock')->group(function () {

    Route::post('/add', [StockController::class, 'addStock']);
    Route::post('/remove', [StockController::class, 'removeStock']);
    Route::post('/adjust', [StockController::class, 'adjustStock']);
    Route::post('/transfer', [StockController::class, 'transferStock']);

    Route::post('/inventory/start', [StockController::class, 'startInventory']);
    Route::post('/inventory/complete', [StockController::class, 'completeInventory']);
    Route::get('/inventory/current', [StockController::class, 'currentInventory']);

    Route::get('/alerts', [StockController::class, 'alerts']);
    Route::get('/report/daily', [StockController::class, 'dailyReport']);
    Route::get('/report/monthly', [StockController::class, 'monthlyReport']);
    Route::get('/report/valuation', [StockController::class, 'stockValuation']);
});
