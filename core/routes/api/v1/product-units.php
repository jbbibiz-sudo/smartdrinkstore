<?php

// Chemin: app/Http/Controllers/Api/ProductController.php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProductUnitController;

/**
 * Routes API - Unités de produits
 */

Route::prefix('product-units')->group(function () {
    Route::get('/', [ProductUnitController::class, 'index']);
    Route::get('/{id}', [ProductUnitController::class, 'show']);
});