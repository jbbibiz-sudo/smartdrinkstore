<?php
/**
 * =============================================================================
 * ROUTES ACHATS
 * =============================================================================
 * Fichier: routes/api/v1/purchases.php
 * Préfixe: /api/v1/purchases
 */

use App\Http\Controllers\Api\PurchaseController;
use Illuminate\Support\Facades\Route;

// ========================================
// ACHATS (APPROVISIONNEMENTS)
// ========================================

Route::prefix('purchases')->group(function () {
    // Liste des achats
    Route::get('/', [PurchaseController::class, 'index'])
        ->name('api.v1.purchases.index');
    
    // Statistiques des achats
    Route::get('/stats/summary', [PurchaseController::class, 'statistics'])
        ->name('api.v1.purchases.stats');
    
    // Créer un achat
    Route::post('/', [PurchaseController::class, 'store'])
        ->name('api.v1.purchases.store');
    
    // Afficher un achat
    Route::get('/{id}', [PurchaseController::class, 'show'])
        ->name('api.v1.purchases.show');
    
    // Mettre à jour un achat
    Route::put('/{id}', [PurchaseController::class, 'update'])
        ->name('api.v1.purchases.update');
    
    // Confirmer un achat
    Route::post('/{id}/confirm', [PurchaseController::class, 'confirm'])
        ->name('api.v1.purchases.confirm');
    
    // Réceptionner un achat
    Route::post('/{id}/receive', [PurchaseController::class, 'receive'])
        ->name('api.v1.purchases.receive');
    
    // Annuler un achat
    Route::post('/{id}/cancel', [PurchaseController::class, 'cancel'])
        ->name('api.v1.purchases.cancel');
    
    // Supprimer un achat
    Route::delete('/{id}', [PurchaseController::class, 'destroy'])
        ->name('api.v1.purchases.destroy');
});