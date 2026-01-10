<?php
/**
 * =============================================================================
 * ROUTES CONSIGNES (EMBALLAGES)
 * =============================================================================
 * Fichier: routes/api/v1/deposits.php
 * Préfixe: /api/v1/deposits
 */

use App\Http\Controllers\Api\DepositController;
use Illuminate\Support\Facades\Route;

// ========================================
// CONSIGNES (EMBALLAGES)
// ========================================

Route::prefix('deposits')->group(function () {
    // Liste des consignes
    Route::get('/', [DepositController::class, 'index'])
        ->name('api.v1.deposits.index');
    
    // Liste des consignes en attente
    Route::get('/pending/list', [DepositController::class, 'pending'])
        ->name('api.v1.deposits.pending');
    
    // Statistiques des consignes
    Route::get('/stats/summary', [DepositController::class, 'statistics'])
        ->name('api.v1.deposits.stats');
    
    // Afficher une consigne
    Route::get('/{id}', [DepositController::class, 'show'])
        ->name('api.v1.deposits.show');
    
    // Enregistrer une consigne sortante (client prend des emballages)
    Route::post('/outgoing', [DepositController::class, 'storeOutgoing'])
        ->name('api.v1.deposits.outgoing');
    
    // Enregistrer une consigne entrante (fournisseur livre des emballages)
    Route::post('/incoming', [DepositController::class, 'storeIncoming'])
        ->name('api.v1.deposits.incoming');
    
    // Traiter un retour de consigne
    Route::post('/{id}/return', [DepositController::class, 'processReturn'])
        ->name('api.v1.deposits.return');
    
    // Historique des retours d'une consigne
    Route::get('/{id}/returns', [DepositController::class, 'returnHistory'])
        ->name('api.v1.deposits.return.history');
    
    // Supprimer une consigne
    Route::delete('/{id}', [DepositController::class, 'destroy'])
        ->name('api.v1.deposits.destroy');
});

// ========================================
// RETOURS D'EMBALLAGES
// ========================================

Route::prefix('deposit-returns')->group(function () {
    // Liste des retours
    Route::get('/', [DepositController::class, 'returns'])
        ->name('api.v1.deposit-returns.index');
    
    // Afficher un retour
    Route::get('/{id}', [DepositController::class, 'showReturn'])
        ->name('api.v1.deposit-returns.show');
});