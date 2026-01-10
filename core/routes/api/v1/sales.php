<?php
/**
 * =============================================================================
 * ROUTES VENTES
 * =============================================================================
 * Fichier: routes/api/v1/sales.php
 * Préfixe: /api/v1/sales
 */

use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Api\CreditPaymentController;
use Illuminate\Support\Facades\Route;

// ========================================
// VENTES
// ========================================

Route::prefix('sales')->group(function () {
    // Liste des ventes
    Route::get('/', [SaleController::class, 'index'])
        ->name('api.v1.sales.index');
    
    // Statistiques des ventes
    Route::get('/stats/summary', [SaleController::class, 'stats'])
        ->name('api.v1.sales.stats');
    
    // Créer une vente
    Route::post('/', [SaleController::class, 'store'])
        ->name('api.v1.sales.store');
    
    // Afficher une vente
    Route::get('/{id}', [SaleController::class, 'show'])
        ->name('api.v1.sales.show');
    
    // Mettre à jour une vente
    Route::put('/{id}', [SaleController::class, 'update'])
        ->name('api.v1.sales.update');
    
    // Supprimer une vente
    Route::delete('/{id}', [SaleController::class, 'destroy'])
        ->name('api.v1.sales.destroy');
});

// ========================================
// GESTION DES CRÉDITS ET PAIEMENTS
// ========================================

Route::prefix('credits')->group(function () {
    // Liste des crédits
    Route::get('/', [CreditPaymentController::class, 'index'])
        ->name('api.v1.credits.index');
    
    // Statistiques des crédits
    Route::get('/statistics', [CreditPaymentController::class, 'statistics'])
        ->name('api.v1.credits.stats');
    
    // Enregistrer un paiement
    Route::post('/payments', [CreditPaymentController::class, 'store'])
        ->name('api.v1.credits.payment.store');
    
    // Historique des paiements d'une vente
    Route::get('/{saleId}/history', [CreditPaymentController::class, 'history'])
        ->name('api.v1.credits.history');
    
    // Supprimer un paiement
    Route::delete('/payments/{paymentId}', [CreditPaymentController::class, 'destroy'])
        ->name('api.v1.credits.payment.destroy');
});