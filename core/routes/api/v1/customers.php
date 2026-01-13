<?php
/**
 * Routes API - Customers (Clients)
 * Fichier: routes/api/v1/customers.php
 * 
 * ⚠️ IMPORTANT: Les routes spécifiques doivent être AVANT les routes avec {id}
 */

use App\Http\Controllers\Api\CustomerController;
use Illuminate\Support\Facades\Route;

// ==========================================
// ROUTES CUSTOMERS (CLIENTS)
// ==========================================

// ✅ ROUTES SPÉCIFIQUES EN PREMIER (avant {id})
// ==========================================

// Statistiques des clients
Route::get('/customers/stats', [CustomerController::class, 'stats']);


// ✅ ROUTES AVEC PARAMÈTRE {id} APRÈS
// ==========================================

// Liste des clients
Route::get('/customers', [CustomerController::class, 'index']);

// Créer un client
Route::post('/customers', [CustomerController::class, 'store']);

// Obtenir un client spécifique
Route::get('/customers/{id}', [CustomerController::class, 'show']);

// Mettre à jour un client
Route::put('/customers/{id}', [CustomerController::class, 'update']);

// Supprimer un client
Route::delete('/customers/{id}', [CustomerController::class, 'destroy']);

// Historique d'un client (ventes + paiements)
Route::get('/customers/{id}/history', [CustomerController::class, 'history']);

// Paiements d'un client
Route::get('/customers/{id}/payments', [CustomerController::class, 'payments']);

// Enregistrer un paiement de dette
Route::post('/customers/{id}/payments', [CustomerController::class, 'recordPayment']);

// Ajuster le solde d'un client
Route::post('/customers/{id}/adjust-balance', [CustomerController::class, 'adjustBalance']);
