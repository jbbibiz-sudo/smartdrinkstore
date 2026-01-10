<?php
/**
 * Routes Authentification
 * Fichier: routes/api/v1/auth.php
 */

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

// Login (PUBLIC - sans auth)
Route::post('auth/login', [AuthController::class, 'login']);

// Routes protégées (avec auth:sanctum)
Route::middleware('auth:sanctum')->prefix('auth')->group(function () {
    Route::get('user', [AuthController::class, 'user']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('check-session', [AuthController::class, 'checkSession']);
    Route::post('change-password', [AuthController::class, 'changePassword']);
});