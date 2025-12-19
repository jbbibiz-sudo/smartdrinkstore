<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\StockController;
use App\Http\Controllers\Api\ProductController;

Route::prefix('webhooks')->group(function () {
    Route::post('/stock-update', [StockController::class, 'webhookStockUpdate']);
    Route::post('/product-sync', [ProductController::class, 'webhookProductSync']);
});
