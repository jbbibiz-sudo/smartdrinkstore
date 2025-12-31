<?php
// api/v1/deposit-types.php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DepositTypeController;

Route::prefix('deposit-types')->group(function () {
    Route::get('/', [DepositTypeController::class, 'index']);
    Route::post('/', [DepositTypeController::class, 'store']);
    Route::get('/{id}', [DepositTypeController::class, 'show']);
    Route::put('/{id}', [DepositTypeController::class, 'update']);
    Route::delete('/{id}', [DepositTypeController::class, 'destroy']);
});