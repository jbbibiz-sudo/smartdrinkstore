<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\StockController;

Route::middleware('auth:sanctum')->prefix('v1')->group(function () {

    Route::middleware('admin')->group(function () {
        Route::post('/products/bulk-delete-permanent', [ProductController::class, 'bulkDeletePermanent']);
        Route::delete('/categories/{category}/force', [CategoryController::class, 'forceDelete']);
        Route::post('/stock/reset-all', [StockController::class, 'resetAllStock']);
    });

    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});
