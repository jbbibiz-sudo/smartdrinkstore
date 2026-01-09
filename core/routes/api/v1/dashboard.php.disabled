<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\StatsController;

// Routes dashboard - déjà dans le groupe prefix('v1')
// Pas besoin de Route::prefix('dashboard') ici

// Statistiques principales du dashboard
Route::get('/dashboard/stats', [StatsController::class, 'dashboard']);
Route::get('/dashboard', [StatsController::class, 'dashboard']);

// Statistiques des ventes
Route::get('/dashboard/sales', [StatsController::class, 'sales']);

// Statistiques des produits
Route::get('/dashboard/products', [StatsController::class, 'products']);