<?php
// Chemin: variants/desktop/backend/routes/api/v1/suppliers.php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SupplierController;

Route::prefix('suppliers')->group(function () {
    // Routes de base
    Route::get('/', [SupplierController::class, 'index']);
    Route::post('/', [SupplierController::class, 'store']);
    Route::get('/search', [SupplierController::class, 'search']); // ⚠️ AVANT /{id}
    Route::get('/stats', [SupplierController::class, 'stats']); // ⚠️ AVANT /{id}
    Route::get('/{id}', [SupplierController::class, 'show']);
    Route::put('/{id}', [SupplierController::class, 'update']);
    Route::delete('/{id}', [SupplierController::class, 'destroy']);
    
    // Relations
    Route::get('/{id}/products', [SupplierController::class, 'products']);
    Route::get('/{id}/purchases', [SupplierController::class, 'purchases']);
});