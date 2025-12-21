<?php

// core/routes/api.php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| API Routes pour SmartDrinkStore Desktop
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    // ====================================
    // CATEGORIES
    // ====================================
    
    require __DIR__ . '/api/v1/subcategories.php';
    
    // ====================================
    // PRODUITS
    // ====================================
    
    Route::get('/products', function () {
        try {
            $products = DB::table('products')
                ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
                ->leftJoin('subcategories', 'products.subcategory_id', '=', 'subcategories.id')
                ->select(
                    'products.*',
                    'categories.name as category_name',
                    'subcategories.name as subcategory_name'
                )
                ->orderBy('products.name')
                ->get();
            
            $formatted = $products->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'unit_price' => (float) $product->unit_price,
                    'stock' => (int) $product->stock,
                    'min_stock' => (int) $product->min_stock,
                    'category_id' => $product->category_id,
                    'subcategory_id' => $product->subcategory_id,
                    'category' => [
                        'id' => $product->category_id,
                        'name' => $product->category_name ?? 'N/A'
                    ],
                    'subcategory' => $product->subcategory_id ? [
                        'id' => $product->subcategory_id,
                        'name' => $product->subcategory_name
                    ] : null
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => $formatted
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des produits',
                'error' => $e->getMessage()
            ], 500);
        }
    });
    
    Route::post('/products', function (Request $request) {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:products',
                'sku' => 'required|string|max:50|unique:products',
                'code' => 'nullable|string|max:50|unique:products',
                'unit_price' => 'required|numeric|min:0',
                'stock' => 'required|integer|min:0',
                'min_stock' => 'required|integer|min:0',
                'category_id' => 'nullable|exists:categories,id',
                'image' => 'nullable|string',
                'description' => 'nullable|string'
            ]);
            
            $id = DB::table('products')->insertGetId([
                'name' => $validated['name'],
                'sku' => $validated['sku'],
                'code' => $validated['code'] ?? $validated['sku'], // Utilise le code fourni ou le SKU par défaut
                'unit_price' => $validated['unit_price'],
                'stock' => $validated['stock'],
                'min_stock' => $validated['min_stock'],
                'category_id' => $validated['category_id'] ?? null,
                'image' => $validated['image'] ?? null,
                'description' => $validated['description'] ?? null,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            $product = DB::table('products')->where('id', $id)->first();
            
            return response()->json([
                'success' => true,
                'message' => 'Produit créé avec succès',
                'data' => $product
            ], 201);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation échouée',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création',
                'error' => $e->getMessage()
            ], 500);
        }
    });

    Route::put('/products/{id}', function (Request $request, $id) {
        try {
            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255|unique:products,name,' . $id,
                'sku' => 'sometimes|required|string|max:50|unique:products,sku,' . $id,
                'code' => 'nullable|string|max:50|unique:products,code,' . $id,
                'unit_price' => 'sometimes|required|numeric|min:0',
                'stock' => 'sometimes|required|integer|min:0',
                'min_stock' => 'sometimes|required|integer|min:0',
                'category_id' => 'nullable|exists:categories,id',
                'image' => 'nullable|string',
                'description' => 'nullable|string'
            ]);
            
            $validated['updated_at'] = now();
            
            DB::table('products')
                ->where('id', $id)
                ->update($validated);
            
            $product = DB::table('products')->where('id', $id)->first();
            
            return response()->json([
                'success' => true,
                'message' => 'Produit mis à jour avec succès',
                'data' => $product
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour',
                'error' => $e->getMessage()
            ], 500);
        }
    });
    
    Route::delete('/products/{id}', function ($id) {
        try {
            DB::table('products')->where('id', $id)->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Produit supprimé avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression',
                'error' => $e->getMessage()
            ], 500);
        }
    });
    
    // ====================================
    // STATISTIQUES DASHBOARD
    // ====================================
    
    Route::get('/dashboard/stats', function () {
        try {
            $totalProducts = DB::table('products')->count();
            
            $lowStock = DB::table('products')
                ->whereColumn('stock', '<=', 'min_stock')
                ->where('stock', '>', 0)
                ->count();
            
            $outOfStock = DB::table('products')
                ->where('stock', '=', 0)
                ->count();
            
            $totalStockValue = DB::table('products')
                ->selectRaw('SUM(stock * unit_price) as total')
                ->value('total') ?? 0;
            
            return response()->json([
                'success' => true,
                'data' => [
                    'total_products' => $totalProducts,
                    'low_stock_count' => $lowStock,
                    'out_of_stock' => $outOfStock,
                    'total_stock_value' => (float) $totalStockValue
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des statistiques',
                'error' => $e->getMessage()
            ], 500);
        }
    });
    
    // ====================================
    // ALERTES DE STOCK
    // ====================================
    
    Route::get('/stock/alerts', function () {
        try {
            $lowStock = DB::table('products')
                ->whereColumn('stock', '<=', 'min_stock')
                ->where('stock', '>', 0)
                ->orderBy('stock', 'asc')
                ->get();
            
            $outOfStock = DB::table('products')
                ->where('stock', '=', 0)
                ->orderBy('name')
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'low_stock' => $lowStock,
                    'out_of_stock' => $outOfStock
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des alertes',
                'error' => $e->getMessage()
            ], 500);
        }
    });
