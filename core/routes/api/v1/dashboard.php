<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DashboardController;

Route::prefix('dashboard')->group(function () {

    Route::get('/stats', [DashboardController::class, 'statistics']);
    Route::get('/', [DashboardController::class, 'index']);
    
    Route::get('/charts/stock-evolution', [DashboardController::class, 'stockEvolution']);
    Route::get('/charts/top-products', [DashboardController::class, 'topProducts']);
    Route::get('/charts/low-stock-by-category', [DashboardController::class, 'lowStockByCategory']);
    Route::get('/charts/stock-value', [DashboardController::class, 'stockValue']);
});
