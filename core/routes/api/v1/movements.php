<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\StockController;

Route::prefix('movements')->group(function () {

    Route::get('/', [StockController::class, 'movements']);
    Route::get('/{product}', [StockController::class, 'productMovements']);

    Route::post('/', [StockController::class, 'createMovement']);
});