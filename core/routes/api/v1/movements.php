<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\StockMovementController;

// Routes pour les mouvements de stock
// Ces routes sont déjà dans le groupe prefix('v1'), pas besoin de préfixe supplémentaire

// Liste tous les mouvements
Route::get('/movements', function () {
    try {
        $movements = \App\Models\StockMovement::with(['product'])
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $movements
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors du chargement des mouvements',
            'error' => $e->getMessage()
        ], 500);
    }
});

// Mouvements d'un produit spécifique
Route::get('/movements/{product}', function ($productId) {
    try {
        $movements = \App\Models\StockMovement::where('product_id', $productId)
            ->with(['product'])
            ->orderBy('created_at', 'desc')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $movements
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors du chargement des mouvements du produit',
            'error' => $e->getMessage()
        ], 500);
    }
});

// Créer un nouveau mouvement (utilise votre contrôleur)
Route::post('/movements', [StockMovementController::class, 'store']);