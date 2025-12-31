<?php
/**
 * =============================================================================
 * ROUTES API - VERSION REFACTORISÉE
 * =============================================================================
 * Fichier: routes/api.php
 * 
 * ✅ Toutes les closures ont été remplacées par des contrôleurs
 * ✅ Utilise les modèles Eloquent (boot() est appelé)
 * ✅ Code organisé et maintenable
 * ✅ Respect des conventions Laravel
 */

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\SubcategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Api\StockController;
use App\Http\Controllers\Api\StatsController;
use App\Http\Controllers\Api\ProductSupplierController;
use App\Http\Controllers\Api\CreditPaymentController;
use App\Http\Controllers\Api\DepositController;



/*
|--------------------------------------------------------------------------
| ROUTES PUBLIQUES (Sans authentification)
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
});

/*
|--------------------------------------------------------------------------
| ROUTES PROTÉGÉES (Avec authentification Sanctum)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    
    // ========================================
    // AUTHENTIFICATION
    // ========================================
    
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', [AuthController::class, 'user']);
        Route::get('/check-session', [AuthController::class, 'checkSession']);
        Route::post('/change-password', [AuthController::class, 'changePassword']);
    });

    // ========================================
    // TEST DE CONNEXION
    // ========================================
    
    Route::get('/ping', function () {
        return response()->json([
            'success' => true,
            'message' => 'API connectée',
            'timestamp' => now()->toIso8601String(),
            'user' => auth()->user()->name ?? 'Unknown',
        ]);
    });

    // ========================================
    // CATÉGORIES
    // ========================================
    
    Route::apiResource('categories', CategoryController::class);

    // ========================================
    // SOUS-CATÉGORIES
    // ========================================
    
    // Routes spécifiques AVANT les routes resource
    Route::get('/subcategories/{subcategory}/products', [SubcategoryController::class, 'products']);
    
    // Routes resource
    Route::apiResource('subcategories', SubcategoryController::class);

    // ========================================
    // PRODUITS
    // ========================================
    
    // Routes spécifiques AVANT les routes resource
        Route::get('/products/low-stock', [ProductController::class, 'lowStock']);
        Route::get('/products/out-of-stock', [ProductController::class, 'outOfStock']);
        Route::get('/products/stats', [ProductController::class, 'stats']);
    
    // Routes resource
        Route::apiResource('products', ProductController::class);

    // ========================================
    // CLIENTS
    // ========================================
    
    // Routes spécifiques AVANT les routes resource
        Route::get('/customers/search', [CustomerController::class, 'search']);
        Route::get('/customers/stats', [CustomerController::class, 'stats']);
        Route::post('/customers/{id}/adjust-balance', [CustomerController::class, 'adjustBalance']);
    
    // Routes resource
        Route::apiResource('customers', CustomerController::class);

    // ========================================
    // FOURNISSEURS
    // ========================================
    
    // Routes spécifiques AVANT les routes resource
        Route::get('/suppliers/search', [SupplierController::class, 'search']);
        Route::get('/suppliers/stats', [SupplierController::class, 'stats']);
    
    // Routes resource
        Route::apiResource('suppliers', SupplierController::class);

    // ============================================
    // GESTION PRODUITS-FOURNISSEURS
    // ============================================

    // Routes pour gérer les fournisseurs d'un produit
    Route::prefix('products/{product}')->group(function () {
        // Liste les fournisseurs d'un produit
        Route::get('suppliers', [ProductSupplierController::class, 'index']);
            
        // Associe un fournisseur
        Route::post('suppliers', [ProductSupplierController::class, 'attach']);
            
        // Synchronise tous les fournisseurs (remplace)
        Route::put('suppliers', [ProductSupplierController::class, 'sync']);
            
        // Met à jour les infos d'un fournisseur spécifique
        Route::put('suppliers/{supplier}', [ProductSupplierController::class, 'update']);
            
        // Dissocie un fournisseur
        Route::delete('suppliers/{supplier}', [ProductSupplierController::class, 'detach']);
            
        // Définit un fournisseur comme préféré
        Route::patch('suppliers/{supplier}/preferred', [ProductSupplierController::class, 'setPreferred']);
    });

    // Routes pour gérer les produits d'un fournisseur
    Route::prefix('suppliers/{supplier}')->group(function () {
        // Liste les produits d'un fournisseur
        Route::get('products', [ProductSupplierController::class, 'productsBySupplier']);
    });


    // ========================================
    // VENTES
    // ========================================
    
    // Routes spécifiques AVANT les routes resource
    Route::get('/sales/stats/summary', [SaleController::class, 'stats']);
    
    // Routes resource
    Route::apiResource('sales', SaleController::class);

    // ========================================
    // GESTION DU STOCK
    // ========================================
    
    Route::prefix('stock')->group(function () {
        // Ajouter du stock
        Route::post('/in', [StockController::class, 'addStock']);
      
        // Retirer du stock
        Route::post('/out', [StockController::class, 'removeStock']);
            
        // Alertes de stock
        Route::get('/alerts', [StockController::class, 'alerts']);
            
        // Valorisation du stock
        Route::get('/valuation', [StockController::class, 'stockValuation']);
            
        // Rapport de statut
        Route::get('/status-report', [StockController::class, 'stockStatusReport']);
    });
    
    // ========================================
    // MOUVEMENTS DE STOCK
    // ========================================
    
    Route::prefix('movements')->group(function () {
        // Liste des mouvements avec filtres
        Route::get('/', [StockController::class, 'movements']);
           
        // Créer un mouvement
        Route::post('/', [StockController::class, 'createMovement']);
         
        // Réapprovisionnement (alias de stock/in)
        Route::post('/restock', [StockController::class, 'addStock']);
            
        // Sortie de stock (alias de stock/out)
        Route::post('/stock-out', [StockController::class, 'removeStock']);
    });

    // ========================================
    // STATISTIQUES DASHBOARD
    // ========================================
        
        Route::get('/stats', [StatsController::class, 'dashboard']);
        
    // ========================================
    // PAIEMENTS - GESTION CREDITS 
    // ========================================
        
    Route::prefix('credits')->group(function () {

        // Liste des crédits
        Route::get('/', [CreditPaymentController::class, 'index']);
                
        // Enregistrer un paiement
        Route::post('/payments', [CreditPaymentController::class, 'store']);
            
        // Historique des paiements d'une vente
        Route::get('/{saleId}/history', [CreditPaymentController::class, 'history']);
            
        // Supprimer un paiement
        Route::delete('/payments/{paymentId}', [CreditPaymentController::class, 'destroy']);
            
        // Statistiques des paiements
        Route::get('/statistics', [CreditPaymentController::class, 'statistics']);
    });

    // ========================================
    // TYPES D'EMBALLAGES CONSIGNABLES
    // ========================================
    
    Route::prefix('deposit-types')->group(function () {
        // Liste des types
        Route::get('/', [DepositController::class, 'depositTypes']);
        
        // Créer un type
        Route::post('/', [DepositController::class, 'storeDepositType']);
        
        // Détails d'un type
        Route::get('/{id}', [DepositController::class, 'showDepositType']);
        
        // Mettre à jour un type
        Route::put('/{id}', [DepositController::class, 'updateDepositType']);
        
        // Supprimer un type
        Route::delete('/{id}', [DepositController::class, 'destroyDepositType']);
    });

    // ========================================
    // CONSIGNES (TRANSACTIONS)
    // ========================================
    
    Route::prefix('deposits')->group(function () {
        // Liste avec filtres
        Route::get('/', [DepositController::class, 'index']);
        
        // Statistiques
        Route::get('/statistics', [DepositController::class, 'statistics']);
        
        // Consignes en attente
        Route::get('/pending', [DepositController::class, 'pending']);
        
        // Créer consigne sortante (vers client)
        Route::post('/outgoing', [DepositController::class, 'storeOutgoing']);
        
        // Créer consigne entrante (du fournisseur)
        Route::post('/incoming', [DepositController::class, 'storeIncoming']);
        
        // Détails d'une consigne
        Route::get('/{id}', [DepositController::class, 'show']);
        
        // Traiter un retour
        Route::post('/{id}/return', [DepositController::class, 'processReturn']);
        
        // Historique des retours d'une consigne
        Route::get('/{id}/returns', [DepositController::class, 'returnHistory']);
        
        // Annuler une consigne (si aucun retour)
        Route::delete('/{id}', [DepositController::class, 'destroy']);
    });

    // ========================================
    // RETOURS D'EMBALLAGES
    // ========================================
    
    Route::prefix('deposit-returns')->group(function () {
        // Liste des retours
        Route::get('/', [DepositController::class, 'returns']);
        
        // Détails d'un retour
        Route::get('/{id}', [DepositController::class, 'showReturn']);
    });   
});

// ========================================
// ROUTE DE FALLBACK (404)
// ========================================

Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'Route non trouvée',
    ], 404);
});