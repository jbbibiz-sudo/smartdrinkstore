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
    // Categories - AJOUTE CECI
    Route::post('categories', function (Request $request) {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:categories'
            ]);

            $id = DB::table('categories')->insertGetId([
                'name' => $validated['name'],
                'created_at' => now(),
                'updated_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Catégorie créée avec succès',
                'data' => DB::table('categories')->where('id', $id)->first()
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création',
                'error' => $e->getMessage()
            ], 500);
        }
    });

    Route::put('categories/{id}', function (Request $request, $id) {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:categories,name,' . $id
            ]);

            DB::table('categories')
                ->where('id', $id)
                ->update([
                    'name' => $validated['name'],
                    'updated_at' => now()
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Catégorie mise à jour avec succès',
                'data' => DB::table('categories')->where('id', $id)->first()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour',
                'error' => $e->getMessage()
            ], 500);
        }
    });

    Route::delete('categories/{id}', function ($id) {
        try {
            if (DB::table('products')->where('category_id', $id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de supprimer : des produits sont liés à cette catégorie'
                ], 400);
            }

            DB::table('categories')->where('id', $id)->delete();

            return response()->json([
                'success' => true,
                'message' => 'Catégorie supprimée avec succès'
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
    // CATEGORIES
    // ====================================
    
    require __DIR__ . '/api/v1/subcategories.php';
    
    // ====================================
    // PRODUITS
    // ====================================
    
    // Liste des produits
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
    
    // Créer un produit
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
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de base de données (Doublon ou contrainte)',
                'error' => $e->getMessage()
            ], 409);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création',
                'error' => $e->getMessage()
            ], 500);
        }
    });

    // Retirer du stock (sortie)
    Route::post('/stock/out', function (Request $request) {
        try {
            $validated = $request->validate([
                'product_id' => 'required|exists:products,id',
                'quantity' => 'required|integer|min:1',
                'reason' => 'nullable|string|max:255'
            ]);

            DB::beginTransaction();

            // Récupérer le produit
            $product = DB::table('products')->where('id', $validated['product_id'])->first();

            // Vérifier qu'il y a assez de stock
            if ($product->stock < $validated['quantity']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock insuffisant',
                    'error' => "Stock actuel: {$product->stock}, demandé: {$validated['quantity']}"
                ], 400);
            }

            // Mettre à jour le stock
            $newStock = $product->stock - $validated['quantity'];
            DB::table('products')
                ->where('id', $validated['product_id'])
                ->update([
                    'stock' => $newStock,
                    'updated_at' => now()
                ]);

            // Enregistrer le mouvement
            DB::table('stock_movements')->insert([
                'product_id' => $validated['product_id'],
                'type' => 'out',
                'quantity' => $validated['quantity'],
                'reason' => $validated['reason'] ?? 'Sortie de stock',
                'created_at' => now()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Stock retiré avec succès',
                'data' => [
                    'product_id' => $validated['product_id'],
                    'old_stock' => $product->stock,
                    'new_stock' => $newStock,
                    'quantity_removed' => $validated['quantity']
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du retrait de stock',
                'error' => $e->getMessage()
            ], 500);
        }
    });
    
    // Mettre à jour un produit
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
    
    // Supprimer un produit
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
    
    // ====================================
    // GESTION DU STOCK
    // ====================================
    
    // Ajouter du stock
    Route::post('/stock/in', function (Request $request) {
        try {
            $validated = $request->validate([
                'product_id' => 'required|exists:products,id',
                'quantity' => 'required|integer|min:1',
                'reason' => 'nullable|string|max:255'
            ]);
            
            DB::beginTransaction();
            
            // Récupérer le produit
            $product = DB::table('products')->where('id', $validated['product_id'])->first();
            
            // Mettre à jour le stock
            $newStock = $product->stock + $validated['quantity'];
            DB::table('products')
                ->where('id', $validated['product_id'])
                ->update([
                    'stock' => $newStock,
                    'updated_at' => now()
                ]);
            
            // Enregistrer le mouvement (si vous avez une table stock_movements)
            try {
                DB::table('stock_movements')->insert([
                    'product_id' => $validated['product_id'],
                    'type' => 'in',
                    'quantity' => $validated['quantity'],
                    'reason' => $validated['reason'] ?? 'Réapprovisionnement',
                    'created_at' => now()
                ]);
            } catch (\Exception $e) {
                // Table n'existe pas encore, ignorer
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Stock ajouté avec succès',
                'data' => [
                    'product_id' => $validated['product_id'],
                    'old_stock' => $product->stock,
                    'new_stock' => $newStock,
                    'quantity_added' => $validated['quantity']
                ]
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'ajout de stock',
                'error' => $e->getMessage()
            ], 500);
        }
    });
    
    // ====================================
    // CATÉGORIES
    // ====================================
    
    Route::get('/categories', function () {
        try {
            $categories = DB::table('categories')
                ->orderBy('name')
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $categories
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des catégories',
                'error' => $e->getMessage()
            ], 500);
        }
    });
    
    // ====================================
    // MOUVEMENTS DE STOCK
    // ====================================

    // Lister les mouvements de stock
    Route::get('/movements', function (Request $request) {
        try {
            $query = DB::table('stock_movements')
                ->join('products', 'stock_movements.product_id', '=', 'products.id')
                ->select(
                    'stock_movements.*',
                    'products.name as product_name',
                    'products.sku as product_sku'
            )
            ->orderBy('stock_movements.created_at', 'desc');
        
        // Filtres optionnels
        if ($request->has('product_id')) {
            $query->where('stock_movements.product_id', $request->product_id);
        }
        
        if ($request->has('type')) {
            $query->where('stock_movements.type', $request->type);
        }
        
        if ($request->has('date_from')) {
            $query->where('stock_movements.created_at', '>=', $request->date_from);
        }
        
        if ($request->has('date_to')) {
            $query->where('stock_movements.created_at', '<=', $request->date_to);
        }
        
        // Pagination
        $perPage = $request->get('per_page', 50);
        $movements = $query->limit($perPage)->get();
        
        return response()->json([
            'success' => true,
            'data' => $movements
        ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des mouvements',
                'error' => $e->getMessage()
            ], 500);
        }
    });

    // Statistiques des mouvements
    Route::get('/movements/stats', function (Request $request) {
        try {
            $today = now()->startOfDay();
            $thisWeek = now()->startOfWeek();
            $thisMonth = now()->startOfMonth();
            
            $stats = [
                'today' => [
                    'in' => DB::table('stock_movements')
                        ->where('type', 'in')
                        ->where('created_at', '>=', $today)
                        ->sum('quantity'),
                    'out' => DB::table('stock_movements')
                        ->where('type', 'out')
                        ->where('created_at', '>=', $today)
                        ->sum('quantity')
                ],
                'this_week' => [
                    'in' => DB::table('stock_movements')
                        ->where('type', 'in')
                        ->where('created_at', '>=', $thisWeek)
                        ->sum('quantity'),
                    'out' => DB::table('stock_movements')
                        ->where('type', 'out')
                        ->where('created_at', '>=', $thisWeek)
                        ->sum('quantity')
                ],
                'this_month' => [
                    'in' => DB::table('stock_movements')
                        ->where('type', 'in')
                        ->where('created_at', '>=', $thisMonth)
                        ->sum('quantity'),
                    'out' => DB::table('stock_movements')
                        ->where('type', 'out')
                        ->where('created_at', '>=', $thisMonth)
                        ->sum('quantity')
                ],
                'total_movements' => DB::table('stock_movements')->count()
            ];
            
            return response()->json([
                'success' => true,
                'data' => $stats
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
    // HEALTH CHECK
    // ====================================
    
    Route::get('/health', function () {
        return response()->json([
            'success' => true,
            'message' => 'API SmartDrinkStore opérationnelle',
            'timestamp' => now()->toIso8601String(),
            'database' => DB::connection()->getPdo() ? 'Connected' : 'Disconnected'
        ]);
    });
});
