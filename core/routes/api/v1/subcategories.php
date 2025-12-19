<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SubcategoryController;

Route::prefix('subcategories')->group(function () {

    Route::get('/', [SubcategoryController::class, 'index']);
    Route::get('/{subcategory}', [SubcategoryController::class, 'show']);
    Route::post('/', [SubcategoryController::class, 'store']);
    Route::put('/{subcategory}', [SubcategoryController::class, 'update']);
    Route::delete('/{subcategory}', [SubcategoryController::class, 'destroy']);

    Route::get('/{subcategory}/products', [SubcategoryController::class, 'products']);
});
