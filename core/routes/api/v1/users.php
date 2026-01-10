<?php
/**
 * =============================================================================
 * ROUTES UTILISATEURS
 * =============================================================================
 * Fichier: routes/api/v1/users.php
 * Préfixe: /api/v1/users
 */

use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

// ========================================
// Toutes les routes nécessitent auth:sanctum
// (déjà appliqué dans api.php)
// ========================================

Route::prefix('users')->group(function () {
    // Liste des utilisateurs
    Route::get('/', [UserController::class, 'index'])
        ->name('api.v1.users.index');
    
    // Statistiques
    Route::get('/stats', [UserController::class, 'stats'])
        ->name('api.v1.users.stats');
    
    // Créer un utilisateur
    Route::post('/', [UserController::class, 'store'])
        ->name('api.v1.users.store');
    
    // Afficher un utilisateur
    Route::get('/{id}', [UserController::class, 'show'])
        ->name('api.v1.users.show');
    
    // Mettre à jour un utilisateur
    Route::put('/{id}', [UserController::class, 'update'])
        ->name('api.v1.users.update');
    
    // Activer/Désactiver un utilisateur
    Route::patch('/{id}/toggle-active', [UserController::class, 'toggleActive'])
        ->name('api.v1.users.toggle');
    
    // Supprimer un utilisateur
    Route::delete('/{id}', [UserController::class, 'destroy'])
        ->name('api.v1.users.destroy');
});