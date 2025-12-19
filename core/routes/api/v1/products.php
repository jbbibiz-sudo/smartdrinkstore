<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProductController;

Route::prefix('products')->group(function () {
    // Routes de base (CRUD)
    Route::get('/', [ProductController::class, 'index']);
    Route::get('/{product}', [ProductController::class, 'show']);
    Route::post('/', [ProductController::class, 'store']);
    Route::put('/{product}', [ProductController::class, 'update']);
    Route::patch('/{product}', [ProductController::class, 'update']);
    Route::delete('/{product}', [ProductController::class, 'destroy']);

    // Routes de recherche (essentielles)
    Route::get('/search/{term}', [ProductController::class, 'search']);
    Route::get('/sku/{sku}', [ProductController::class, 'findBySku']);

    // Routes statistiques et alertes
    Route::get('/stats/overview', [ProductController::class, 'stats']);
    Route::get('/alerts/low-stock', [ProductController::class, 'lowStock']);
    Route::get('/alerts/out-of-stock', [ProductController::class, 'outOfStock']);

    // Commenter ou supprimer les routes non implémentées pour l'instant
    // Route::get('/barcode/{barcode}', [ProductController::class, 'findByBarcode']);
    // Route::post('/{product}/image', [ProductController::class, 'uploadImage']);
    // Route::delete('/{product}/image', [ProductController::class, 'deleteImage']);
    // Route::post('/import', [ProductController::class, 'import']);
    // Route::get('/export', [ProductController::class, 'export']);
    // Route::post('/bulk-update', [ProductController::class, 'bulkUpdate']);
    // Route::post('/bulk-delete', [ProductController::class, 'bulkDelete']);
    // Route::post('/bulk-activate', [ProductController::class, 'bulkActivate']);
    // Route::post('/bulk-deactivate', [ProductController::class, 'bulkDeactivate']);
});