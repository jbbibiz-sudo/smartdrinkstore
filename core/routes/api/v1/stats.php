<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\StatsController;

// Routes stats - déjà dans le groupe prefix('v1')
// Route directe pour /stats qui utilise votre StatsController existant

Route::get('/stats', [StatsController::class, 'dashboard']);
Route::get('/stats/sales', [StatsController::class, 'sales']);
Route::get('/stats/products', [StatsController::class, 'products']);