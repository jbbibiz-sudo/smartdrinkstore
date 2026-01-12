<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\StockController;
use App\Http\Controllers\Api\StockAdjustmentController;

Route::prefix('stock')->group(function () {

    Route::post('/add', [StockController::class, 'addStock']);
    Route::post('/remove', [StockController::class, 'removeStock']);
    Route::post('/adjust', [StockController::class, 'adjustStock']);
    Route::post('/transfer', [StockController::class, 'transferStock']);

    Route::post('/inventory/start', [StockController::class, 'startInventory']);
    Route::post('/inventory/complete', [StockController::class, 'completeInventory']);
    Route::get('/inventory/current', [StockController::class, 'currentInventory']);

    // Ajuster le stock (entrée/sortie avec conversion)
    Route::post('/adjust', [StockAdjustmentController::class, 'adjust']);
    
    // Obtenir le stock détaillé d'un produit
    Route::get('/{product}/details', [StockAdjustmentController::class, 'getStock']);
    
    // Historique des mouvements
    Route::get('/{product}/movements', [StockAdjustmentController::class, 'movements']);

    Route::get('/alerts', [StockController::class, 'alerts']);
    Route::get('/report/daily', [StockController::class, 'dailyReport']);
    Route::get('/report/monthly', [StockController::class, 'monthlyReport']);
    Route::get('/report/valuation', [StockController::class, 'stockValuation']);
});
