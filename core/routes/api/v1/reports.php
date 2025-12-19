<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

// ============================================
// Routes pour les rapports et statistiques
// ============================================

// Statistiques générales
Route::get('/reports/statistics', function () {
    try {
        $totalProducts = DB::table('products')->count();
        
        $totalStockValue = DB::table('products')
            ->sum(DB::raw('stock * unit_price'));
        
        $lowStockCount = DB::table('products')
            ->whereRaw('stock <= min_stock')
            ->where('stock', '>', 0)
            ->count();
        
        $outOfStockCount = DB::table('products')
            ->where('stock', '=', 0)
            ->count();
        
        return response()->json([
            'total_products' => $totalProducts ?? 0,
            'total_stock_value' => $totalStockValue ?? 0,
            'low_stock_count' => $lowStockCount ?? 0,
            'out_of_stock_count' => $outOfStockCount ?? 0
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Erreur lors de la récupération des statistiques',
            'message' => $e->getMessage()
        ], 500);
    }
});

// Alertes de stock
Route::get('/reports/alerts', function () {
    try {
        // Produits en rupture de stock
        $outOfStock = DB::table('products')
            ->where('stock', '=', 0)
            ->get(['id', 'name', 'sku', 'stock', 'min_stock', 'unit_price']);
        
        // Produits avec stock faible
        $lowStock = DB::table('products')
            ->whereRaw('stock <= min_stock')
            ->where('stock', '>', 0)
            ->get(['id', 'name', 'sku', 'stock', 'min_stock', 'unit_price']);
        
        return response()->json([
            'out_of_stock' => $outOfStock,
            'low_stock' => $lowStock
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Erreur lors de la récupération des alertes',
            'message' => $e->getMessage()
        ], 500);
    }
});

// Export inventaire (Excel)
Route::get('/reports/export/inventory', function () {
    try {
        // Pour l'instant, retourner un JSON
        // TODO: Implémenter l'export Excel avec PhpSpreadsheet
        $products = DB::table('products')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->select(
                'products.id',
                'products.name',
                'products.sku',
                'products.barcode',
                'categories.name as category_name',
                'products.unit_price',
                'products.cost_price',
                'products.stock',
                'products.min_stock',
                'products.max_stock'
            )
            ->get();
        
        return response()->json([
            'data' => $products,
            'export_date' => now()->format('Y-m-d H:i:s')
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Erreur lors de l\'export',
            'message' => $e->getMessage()
        ], 500);
    }
});

// Export mouvements (Excel)
Route::get('/reports/export/movements', function () {
    try {
        // Pour l'instant, retourner un JSON
        // TODO: Implémenter l'export Excel avec PhpSpreadsheet
        $movements = DB::table('stock_movements')
            ->join('products', 'stock_movements.product_id', '=', 'products.id')
            ->select(
                'stock_movements.id',
                'stock_movements.created_at',
                'products.name as product_name',
                'products.sku',
                'stock_movements.movement_type',
                'stock_movements.quantity',
                'stock_movements.unit_price',
                DB::raw('stock_movements.quantity * stock_movements.unit_price as total_price'),
                'stock_movements.notes'
            )
            ->orderBy('stock_movements.created_at', 'desc')
            ->limit(1000)
            ->get();
        
        return response()->json([
            'data' => $movements,
            'export_date' => now()->format('Y-m-d H:i:s')
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Erreur lors de l\'export',
            'message' => $e->getMessage()
        ], 500);
    }
});

// Rapport par catégorie
Route::get('/reports/by-category', function () {
    try {
        $reportByCategory = DB::table('products')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->select(
                'categories.id',
                'categories.name as category_name',
                DB::raw('COUNT(products.id) as total_products'),
                DB::raw('SUM(products.stock) as total_stock'),
                DB::raw('SUM(products.stock * products.unit_price) as total_value')
            )
            ->groupBy('categories.id', 'categories.name')
            ->get();
        
        return response()->json($reportByCategory);
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Erreur lors de la génération du rapport',
            'message' => $e->getMessage()
        ], 500);
    }
});

// Valeur totale du stock
Route::get('/reports/stock-value', function () {
    try {
        $stockValue = DB::table('products')
            ->select(
                DB::raw('SUM(stock * unit_price) as total_selling_value'),
                DB::raw('SUM(stock * cost_price) as total_cost_value'),
                DB::raw('SUM(stock * (unit_price - cost_price)) as potential_profit')
            )
            ->first();
        
        return response()->json($stockValue);
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Erreur lors du calcul de la valeur',
            'message' => $e->getMessage()
        ], 500);
    }
});