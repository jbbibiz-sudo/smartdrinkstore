<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SubcategoryController;

// Routes sous-catégories - déjà dans le groupe prefix('v1')
// Utilise votre SubcategoryController existant qui est déjà complet

// Liste toutes les sous-catégories
Route::get('/subcategories', [SubcategoryController::class, 'index']);

// Récupérer une sous-catégorie par ID
Route::get('/subcategories/{id}', [SubcategoryController::class, 'show']);

// Créer une sous-catégorie
Route::post('/subcategories', [SubcategoryController::class, 'store']);

// Modifier une sous-catégorie
Route::put('/subcategories/{id}', [SubcategoryController::class, 'update']);

// Supprimer une sous-catégorie
Route::delete('/subcategories/{id}', [SubcategoryController::class, 'destroy']);

// Récupérer les produits d'une sous-catégorie
Route::get('/subcategories/{id}/products', [SubcategoryController::class, 'products']);

// Récupérer les sous-catégories d'une catégorie
Route::get('/categories/{categoryId}/subcategories', function ($categoryId) {
    try {
        $subcategories = \App\Models\Subcategory::where('category_id', $categoryId)
            ->orderBy('name')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $subcategories
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors du chargement des sous-catégories',
            'error' => $e->getMessage()
        ], 500);
    }
});