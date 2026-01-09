<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DatabaseController;
use App\Http\Controllers\Api\StockMovementController;

// ========================================
// AUTHENTIFICATION (Public)
// ========================================
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('/user', [AuthController::class, 'user'])->middleware('auth:sanctum');
});

// ========================================
// ROUTES PROTÉGÉES
// ========================================
Route::middleware('auth:sanctum')->group(function () {
    
    // BASE DE DONNÉES
    Route::prefix('database')->group(function () {
        Route::get('/info', [DatabaseController::class, 'info']);
        Route::get('/export', [DatabaseController::class, 'export']);
        Route::post('/import', [DatabaseController::class, 'import']);
        Route::post('/backup', [DatabaseController::class, 'backup']);
        Route::get('/backups', [DatabaseController::class, 'listBackups']);
        Route::post('/restore', [DatabaseController::class, 'restore']);
    });
    
    // DASHBOARD
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
    Route::get('/dashboard/recent-sales', [DashboardController::class, 'recentSales']);
    Route::get('/dashboard/low-stock', [DashboardController::class, 'lowStock']);
    Route::get('/dashboard/top-products', [DashboardController::class, 'topProducts']);
    
    // UTILISATEURS & RÔLES
    Route::apiResource('users', UserController::class);
    Route::apiResource('roles', RoleController::class);
    Route::post('/users/{user}/roles', [UserController::class, 'assignRoles']);
    
    // PRODUITS & CATÉGORIES
    Route::apiResource('products', ProductController::class);
    Route::apiResource('categories', CategoryController::class);
    Route::get('/products/{product}/stock-history', [ProductController::class, 'stockHistory']);
    
    // FOURNISSEURS & CLIENTS
    Route::apiResource('suppliers', SupplierController::class);
    Route::apiResource('customers', CustomerController::class);
    
    // VENTES & ACHATS
    Route::apiResource('sales', SaleController::class);
    Route::apiResource('purchases', PurchaseController::class);
    Route::post('/sales/{sale}/cancel', [SaleController::class, 'cancel']);
    Route::post('/purchases/{purchase}/cancel', [PurchaseController::class, 'cancel']);
    
    // MOUVEMENTS DE STOCK
    Route::apiResource('stock-movements', StockMovementController::class);
    Route::get('/stock-movements/product/{product}', [StockMovementController::class, 'byProduct']);
    
    // FACTURES & PAIEMENTS
    // Route::apiResource('invoices', InvoiceController::class); // Uncomment and define InvoiceController if needed
    // Route::apiResource('payments', PaymentController::class); // Uncomment and define PaymentController if needed
    // Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'generatePdf']); // Uncomment and define InvoiceController if needed
    
    // RAPPORTS
    // Route::prefix('reports')->group(function () {
    //     Route::get('/sales', [ReportController::class, 'sales']);
    //     Route::get('/purchases', [ReportController::class, 'purchases']);
    //     Route::get('/stock', [ReportController::class, 'stock']);
    //     Route::get('/financial', [ReportController::class, 'financial']);
    //     Route::get('/export', [ReportController::class, 'export']);
    // });
});

// FALLBACK 404
Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'Route non trouvée'
    ], 404);
});