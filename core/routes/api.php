<?php
/**
 * Routes API - Architecture Modulaire
 * Fichier: routes/api.php
 */

use Illuminate\Support\Facades\Route;

// ========================================
// VERSION 1 DE L'API
// ========================================

Route::prefix('v1')->group(function () {
    
    // Routes publiques (auth login)
    require __DIR__ . '/api/v1/auth.php';
    
    // Routes protégées
    Route::middleware('auth:sanctum')->group(function () {
        
        // Test de connexion
        Route::get('/ping', function () {
            return response()->json([
                'success' => true,
                'message' => 'API connectée',
                'user' => auth()->user()->name,
            ]);
        });
        
        // Modules
        require __DIR__ . '/api/v1/products.php';
        require __DIR__ . '/api/v1/categories.php';
        require __DIR__ . '/api/v1/subcategories.php';
        require __DIR__ . '/api/v1/customers.php';
        require __DIR__ . '/api/v1/suppliers.php';
        require __DIR__ . '/api/v1/sales.php';
        require __DIR__ . '/api/v1/purchases.php';
        require __DIR__ . '/api/v1/deposits.php';
        require __DIR__ . '/api/v1/stock.php';
        require __DIR__ . '/api/v1/movements.php';
        require __DIR__ . '/api/v1/deposit-types.php';
        require __DIR__ . '/api/v1/users.php';
        require __DIR__ . '/api/v1/dashboard.php';
        require __DIR__ . '/api/v1/stats.php';
        require __DIR__ . '/api/v1/reports.php';
        require __DIR__ . '/api/v1/deposit-stats.php';
        require __DIR__ . '/api/v1/protected.php';
        require __DIR__ . '/api/v1/webhooks.php';
        require __DIR__ . '/api/v1/cron.php';
    });
});

// 404
Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'Route non trouvée',
    ], 404);
});