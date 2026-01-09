<?php
/**
 * =============================================================================
 * ROUTES API - VERSION COMPLÈTE
 * =============================================================================
 * Fichier: routes/api.php
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
use App\Http\Controllers\Api\DepositTypeController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\StockMovementController;

/*
|--------------------------------------------------------------------------
| ROUTES PUBLIQUES (Sans authentification)
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
});

/*
|--------------------------------------------------------------------------
| ROUTES PROTÉGÉES (Avec authentification Sanctum)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {
    
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
    
    Route::get('/subcategories/{subcategory}/products', [SubcategoryController::class, 'products']);
    Route::apiResource('subcategories', SubcategoryController::class);

    // ========================================
    // PRODUITS
    // ========================================
    
    Route::get('/products/low-stock', [ProductController::class, 'lowStock']);
    Route::get('/products/out-of-stock', [ProductController::class, 'outOfStock']);
    Route::get('/products/expired', [ProductController::class, 'expired']);
    Route::get('/products/expiring-soon', [ProductController::class, 'expiringSoon']);
    Route::get('/products/stats', [ProductController::class, 'stats']);
    Route::apiResource('products', ProductController::class);

    // ========================================
    // CLIENTS
    // ========================================
    
    Route::get('/customers/search', [CustomerController::class, 'search']);
    Route::get('/customers/stats', [CustomerController::class, 'stats']);
    Route::post('/customers/{id}/adjust-balance', [CustomerController::class, 'adjustBalance']);
    Route::apiResource('customers', CustomerController::class);

    // ========================================
    // FOURNISSEURS
    // ========================================
    
    Route::get('/suppliers/search', [SupplierController::class, 'search']);
    Route::get('/suppliers/stats', [SupplierController::class, 'stats']);
    Route::apiResource('suppliers', SupplierController::class);

    // ========================================
    // PRODUITS-FOURNISSEURS
    // ========================================

    Route::prefix('products/{product}')->group(function () {
        Route::get('suppliers', [ProductSupplierController::class, 'index']);
        Route::post('suppliers', [ProductSupplierController::class, 'attach']);
        Route::put('suppliers', [ProductSupplierController::class, 'sync']);
        Route::put('suppliers/{supplier}', [ProductSupplierController::class, 'update']);
        Route::delete('suppliers/{supplier}', [ProductSupplierController::class, 'detach']);
        Route::patch('suppliers/{supplier}/preferred', [ProductSupplierController::class, 'setPreferred']);
    });

    Route::prefix('suppliers/{supplier}')->group(function () {
        Route::get('products', [ProductSupplierController::class, 'productsBySupplier']);
    });

    // ========================================
    // VENTES
    // ========================================
    
    Route::get('/sales/stats/summary', [SaleController::class, 'stats']);
    Route::apiResource('sales', SaleController::class);

    // ========================================
    // GESTION DU STOCK
    // ========================================
    
    Route::prefix('stock')->group(function () {
        Route::post('/in', [StockController::class, 'addStock']);
        Route::post('/out', [StockController::class, 'removeStock']);
        Route::get('/alerts', [StockController::class, 'alerts']);
        Route::get('/valuation', [StockController::class, 'stockValuation']);
        Route::get('/status-report', [StockController::class, 'stockStatusReport']);
    });
    
    // ========================================
    // MOUVEMENTS DE STOCK (✅ AJOUTÉ)
    // ========================================
    
    Route::get('/movements', [StockController::class, 'movements']);
    Route::get('/movements/{product}', [StockController::class, 'productMovements']);
    Route::post('/movements', [StockMovementController::class, 'store']);

    // ========================================
    // STATISTIQUES DASHBOARD (✅ AJOUTÉ)
    // ========================================
    
    Route::get('/stats', [StatsController::class, 'dashboard']);
    Route::get('/stats/sales', [StatsController::class, 'sales']);
    Route::get('/stats/products', [StatsController::class, 'products']);
    
    // ========================================
    // DASHBOARD (✅ AJOUTÉ)
    // ========================================
    
    Route::get('/dashboard', [StatsController::class, 'dashboard']);
    Route::get('/dashboard/stats', [StatsController::class, 'dashboard']);
    Route::get('/dashboard/sales', [StatsController::class, 'sales']);
    Route::get('/dashboard/products', [StatsController::class, 'products']);

    // ========================================
    // PAIEMENTS - GESTION CREDITS 
    // ========================================
    
    Route::prefix('credits')->group(function () {
        Route::get('/', [CreditPaymentController::class, 'index']);
        Route::post('/payments', [CreditPaymentController::class, 'store']);
        Route::get('/{saleId}/history', [CreditPaymentController::class, 'history']);
        Route::delete('/payments/{paymentId}', [CreditPaymentController::class, 'destroy']);
        Route::get('/statistics', [CreditPaymentController::class, 'statistics']);
    });

    // ========================================
    // UTILISATEURS
    // ========================================
    
    Route::patch('/users/{user}/toggle-active', [UserController::class, 'toggleActive']);
    Route::get('/users/stats', [UserController::class, 'stats']);
    Route::apiResource('users', UserController::class);

    // ========================================
    // TYPES D'EMBALLAGES
    // ========================================
    
    Route::apiResource('deposit-types', DepositTypeController::class);

    // ========================================
    // CONSIGNES
    // ========================================
    
    Route::get('deposits', [DepositController::class, 'index']);
    Route::get('/deposits/stats/summary', [DepositController::class, 'statistics']);
    Route::get('deposits/{id}', [DepositController::class, 'show']);
    Route::post('deposits/outgoing', [DepositController::class, 'storeOutgoing']);
    Route::post('deposits/incoming', [DepositController::class, 'storeIncoming']);
    Route::post('deposits/{id}/return', [DepositController::class, 'processReturn']);
    Route::delete('deposits/{id}', [DepositController::class, 'destroy']);
    Route::get('deposits/pending/list', [DepositController::class, 'pending']);

    // ========================================
    // RETOURS D'EMBALLAGES
    // ========================================
    
    Route::get('deposit-returns', [DepositController::class, 'returns']);
    Route::get('deposit-returns/{id}', [DepositController::class, 'showReturn']);
    Route::get('deposits/{id}/returns', [DepositController::class, 'returnHistory']);

    // ========================================
    // ACHATS
    // ========================================
    
    Route::get('purchases/stats/summary', [PurchaseController::class, 'statistics']);
    Route::post('purchases/{id}/confirm', [PurchaseController::class, 'confirm']);
    Route::post('purchases/{id}/receive', [PurchaseController::class, 'receive']);
    Route::post('purchases/{id}/cancel', [PurchaseController::class, 'cancel']);
    Route::apiResource('purchases', PurchaseController::class);
});

// Logout public (avec token)
Route::post('/auth/logout', [AuthController::class, 'logout'])
    ->middleware('auth:sanctum');

// ========================================
// ROUTE DE FALLBACK (404)
// ========================================

Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'Route non trouvée',
    ], 404);
});