<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/

// ✅ Routes publiques (SANS middleware auth:sanctum)
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->name('api.auth.login');
});

// ✅ Routes protégées (AVEC middleware auth:sanctum)
Route::middleware('auth:sanctum')->prefix('auth')->group(function () {
    Route::get('/user', [AuthController::class, 'user'])->name('api.auth.user');
    Route::post('/logout', [AuthController::class, 'logout'])->name('api.auth.logout');
    Route::get('/check-session', [AuthController::class, 'checkSession'])->name('api.auth.check');
    Route::post('/change-password', [AuthController::class, 'changePassword'])->name('api.auth.password');
});