<?php
// Chemin: C:\smartdrinkstore\core\routes\api.php
// Routes API avec authentification Sanctum - VERSION CORRIGÉE

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\SupplierController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| API Routes pour SmartDrinkStore Desktop
|--------------------------------------------------------------------------
| ✅ Structure corrigée avec ordre de précédence respecté
| ✅ Routes customers et suppliers ajoutées
| ✅ Toutes les routes protégées par Sanctum sauf /auth/login
*/

// ====================================
// ROUTES PUBLIQUES (sans authentification)
// ====================================

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
});

// ====================================
// ROUTES PROTÉGÉES (avec authentification Sanctum)
// ====================================

Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    
    // ====================================
    // AUTHENTIFICATION
    // ====================================
    
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', [AuthController::class, 'user']);
        Route::get('/check-session', [AuthController::class, 'checkSession']);
        Route::post('/change-password', [AuthController::class, 'changePassword']);
    });

    // ====================================
    // TEST DE CONNEXION
    // ====================================
    
    Route::get('/ping', function () {
        return response()->json([
            'success' => true,
            'message' => 'API connectée',
            'timestamp' => now()->toIso8601String(),
            'user' => auth()->user()->name ?? 'Unknown',
        ]);
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
    
    Route::post('/categories', function (Request $request) {
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

    Route::put('/categories/{id}', function (Request $request, $id) {
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

    Route::delete('/categories/{id}', function ($id) {
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
    // SOUS-CATÉGORIES
    // ====================================
    
    Route::get('/subcategories', function () {
        try {
            $subcategories = DB::table('subcategories')
                ->leftJoin('categories', 'subcategories.category_id', '=', 'categories.id')
                ->select(
                    'subcategories.*',
                    'categories.name as category_name'
                )
                ->orderBy('subcategories.name')
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $subcategories
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des sous-catégories',
                'error' => $e->getMessage()
            ], 500);
        }
    });
    
    // ====================================
    // PRODUITS
    // ====================================
    // ⚠️ ROUTES SPÉCIFIQUES AVANT ROUTES PARAMÉTRÉES
    
    // Routes spécifiques d'abord
    Route::get('/products/low-stock', function () {
        try {
            $products = DB::table('products')
                ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
                ->select('products.*', 'categories.name as category_name')
                ->whereRaw('products.stock <= products.min_stock')
                ->orderBy('products.stock', 'asc')
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $products
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement',
                'error' => $e->getMessage()
            ], 500);
        }
    });
    
    // Liste tous les produits
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
            
            return response()->json([
                'success' => true,
                'data' => $products
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
                'name' => 'required|string|max:255',
                'sku' => 'required|string|max:100|unique:products',
                'code' => 'nullable|string|max:100',
                'category_id' => 'required|exists:categories,id',
                'subcategory_id' => 'nullable|exists:subcategories,id',
                'unit_price' => 'required|numeric|min:0',
                'min_stock' => 'required|integer|min:0',
                'stock' => 'required|integer|min:0'
            ]);

            $id = DB::table('products')->insertGetId(array_merge($validated, [
                'created_at' => now(),
                'updated_at' => now()
            ]));

            $product = DB::table('products')
                ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
                ->where('products.id', $id)
                ->select('products.*', 'categories.name as category_name')
                ->first();

            return response()->json([
                'success' => true,
                'message' => 'Produit créé avec succès',
                'data' => $product
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création',
                'error' => $e->getMessage()
            ], 500);
        }
    });
    
    // Route paramétré APRÈS les routes spécifiques
    Route::get('/products/{id}', function ($id) {
        try {
            $product = DB::table('products')
                ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
                ->leftJoin('subcategories', 'products.subcategory_id', '=', 'subcategories.id')
                ->where('products.id', $id)
                ->select(
                    'products.*',
                    'categories.name as category_name',
                    'subcategories.name as subcategory_name'
                )
                ->first();

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Produit non trouvé'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $product
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement',
                'error' => $e->getMessage()
            ], 500);
        }
    });

    Route::put('/products/{id}', function (Request $request, $id) {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'sku' => 'required|string|max:100|unique:products,sku,' . $id,
                'code' => 'nullable|string|max:100',
                'category_id' => 'required|exists:categories,id',
                'subcategory_id' => 'nullable|exists:subcategories,id',
                'unit_price' => 'required|numeric|min:0',
                'min_stock' => 'required|integer|min:0',
                'stock' => 'required|integer|min:0'
            ]);

            DB::table('products')
                ->where('id', $id)
                ->update(array_merge($validated, [
                    'updated_at' => now()
                ]));

            $product = DB::table('products')
                ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
                ->where('products.id', $id)
                ->select('products.*', 'categories.name as category_name')
                ->first();

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
    // CLIENTS (ROUTES CORRIGÉES)
    // ====================================

    // Routes spécifiques d'abord
    Route::get('/customers/search', [CustomerController::class, 'search']);
    Route::get('/customers/stats', [CustomerController::class, 'stats']);
    Route::post('/customers/{id}/adjust-balance', [CustomerController::class, 'adjustBalance']);

    // Resource routes après
    Route::apiResource('customers', CustomerController::class);

    // ====================================
    // FOURNISSEURS (ROUTES CORRIGÉES)
    // ====================================

    // Routes spécifiques d'abord
    Route::get('/suppliers/search', [SupplierController::class, 'search']);
    Route::get('/suppliers/stats', [SupplierController::class, 'stats']);

    // Resource routes après
    Route::apiResource('suppliers', SupplierController::class);
    
    // ====================================
    // MOUVEMENTS DE STOCK
    // ====================================
    
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

            if ($request->has('type')) {
                $query->where('stock_movements.type', $request->type);
            }
            if ($request->has('product_id')) {
                $query->where('stock_movements.product_id', $request->product_id);
            }

            $movements = $query->limit(100)->get();

            return response()->json([
                'success' => true,
                'data' => $movements
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement',
                'error' => $e->getMessage()
            ], 500);
        }
    });

    Route::post('/movements/restock', function (Request $request) {
        try {
            $validated = $request->validate([
                'product_id' => 'required|exists:products,id',
                'quantity' => 'required|integer|min:1',
                'reason' => 'nullable|string|max:500'
            ]);

            DB::beginTransaction();

            DB::table('products')
                ->where('id', $validated['product_id'])
                ->increment('stock', $validated['quantity']);

            DB::table('stock_movements')->insert([
                'product_id' => $validated['product_id'],
                'type' => 'in',
                'quantity' => $validated['quantity'],
                'reason' => $validated['reason'] ?? 'Réapprovisionnement',
                'created_at' => now()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Stock mis à jour avec succès'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du stock',
                'error' => $e->getMessage()
            ], 500);
        }
    });

    Route::post('/movements/stock-out', function (Request $request) {
        try {
            $validated = $request->validate([
                'product_id' => 'required|exists:products,id',
                'quantity' => 'required|integer|min:1',
                'reason' => 'required|string|max:500',
                'reason_type' => 'required|in:damage,loss,adjustment,other'
            ]);

            $product = DB::table('products')->where('id', $validated['product_id'])->first();

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Produit non trouvé'
                ], 404);
            }

            if ($product->stock < $validated['quantity']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock insuffisant'
                ], 400);
            }

            DB::beginTransaction();

            DB::table('products')
                ->where('id', $validated['product_id'])
                ->decrement('stock', $validated['quantity']);

            DB::table('stock_movements')->insert([
                'product_id' => $validated['product_id'],
                'type' => 'out',
                'quantity' => $validated['quantity'],
                'reason' => $validated['reason'],
                'created_at' => now()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Sortie de stock enregistrée avec succès'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'enregistrement',
                'error' => $e->getMessage()
            ], 500);
        }
    });
    
    // ====================================
    // VENTES
    // ====================================
    // ⚠️ ROUTES SPÉCIFIQUES AVANT ROUTES PARAMÉTRÉES
    
    // Routes spécifiques d'abord
    Route::get('/sales/stats/summary', function (Request $request) {
        try {
            $today = now()->startOfDay();
            $thisWeek = now()->startOfWeek();
            $thisMonth = now()->startOfMonth();

            $stats = [
                'today' => [
                    'count' => DB::table('sales')->where('created_at', '>=', $today)->count(),
                    'total' => DB::table('sales')->where('created_at', '>=', $today)->sum('total_amount') ?? 0,
                    'cash' => DB::table('sales')->where('created_at', '>=', $today)->where('payment_method', 'cash')->sum('total_amount') ?? 0,
                    'mobile' => DB::table('sales')->where('created_at', '>=', $today)->where('payment_method', 'mobile')->sum('total_amount') ?? 0,
                    'credit' => DB::table('sales')->where('created_at', '>=', $today)->where('payment_method', 'credit')->sum('total_amount') ?? 0,
                ],
                'this_week' => [
                    'count' => DB::table('sales')->where('created_at', '>=', $thisWeek)->count(),
                    'total' => DB::table('sales')->where('created_at', '>=', $thisWeek)->sum('total_amount') ?? 0,
                ],
                'this_month' => [
                    'count' => DB::table('sales')->where('created_at', '>=', $thisMonth)->count(),
                    'total' => DB::table('sales')->where('created_at', '>=', $thisMonth)->sum('total_amount') ?? 0,
                ],
                'total_sales' => DB::table('sales')->count(),
                'total_revenue' => DB::table('sales')->sum('total_amount') ?? 0,
                'total_credit' => DB::table('customers')->sum('balance') ?? 0,
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement',
                'error' => $e->getMessage()
            ], 500);
        }
    });
    
    Route::post('/sales', function (Request $request) {
        try {
            $validated = $request->validate([
                'customer_id' => 'nullable|exists:customers,id',
                'payment_method' => 'required|in:cash,mobile_money,bank_transfer,credit',
                'type' => 'required|in:counter,wholesale',
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.unit_price' => 'required|numeric|min:0',
                'items.*.subtotal' => 'required|numeric|min:0',
                'total_amount' => 'required|numeric|min:0',
                'discount_amount' => 'nullable|numeric|min:0',
                'invoice_number' => 'required|string|unique:sales,invoice_number'
            ]);

            if ($validated['payment_method'] === 'credit' && !$validated['customer_id']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Un client est requis pour une vente à crédit'
                ], 422);
            }

            DB::beginTransaction();
            
            $saleId = DB::table('sales')->insertGetId([
                'customer_id' => $validated['customer_id'],
                'invoice_number' => $validated['invoice_number'],
                'payment_method' => $validated['payment_method'],
                'type' => $validated['type'],
                'total_amount' => $validated['total_amount'],
                'discount_amount' => $validated['discount_amount'] ?? 0,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            foreach ($validated['items'] as $item) {
                $product = DB::table('products')->where('id', $item['product_id'])->first();
                
                if (!$product) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "Produit introuvable"
                    ], 404);
                }
                
                if ($product->stock < $item['quantity']) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "Stock insuffisant pour {$product->name}. Disponible: {$product->stock}, Demandé: {$item['quantity']}"
                    ], 400);
                }
                
                DB::table('sale_items')->insert([
                    'sale_id' => $saleId,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['subtotal'],
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                
                DB::table('products')
                    ->where('id', $item['product_id'])
                    ->decrement('stock', $item['quantity']);
                
                DB::table('stock_movements')->insert([
                    'product_id' => $item['product_id'],
                    'type' => 'out',
                    'quantity' => $item['quantity'],
                    'reason' => "Vente #{$validated['invoice_number']}",
                    'created_at' => now()
                ]);
            }
            
            if ($validated['payment_method'] === 'credit' && $validated['customer_id']) {
                DB::table('customers')
                    ->where('id', $validated['customer_id'])
                    ->increment('balance', $validated['total_amount']);
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Vente enregistrée avec succès',
                'data' => [
                    'sale_id' => $saleId,
                    'invoice_number' => $validated['invoice_number']
                ]
            ], 201);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'enregistrement de la vente',
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ], 500);
        }
    });
    
    Route::get('/sales', function (Request $request) {
        try {
            $query = DB::table('sales')
                ->leftJoin('customers', 'sales.customer_id', '=', 'customers.id')
                ->select(
                    'sales.*',
                    'customers.name as customer_name',
                    'customers.phone as customer_phone'
                )
                ->orderBy('sales.created_at', 'desc');

            if ($request->has('date_from')) {
                $query->where('sales.created_at', '>=', $request->date_from);
            }
            if ($request->has('date_to')) {
                $query->where('sales.created_at', '<=', $request->date_to);
            }
            if ($request->has('customer_id')) {
                $query->where('sales.customer_id', $request->customer_id);
            }

            $sales = $query->limit(100)->get();

            return response()->json([
                'success' => true,
                'data' => $sales
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement',
                'error' => $e->getMessage()
            ], 500);
        }
    });
    
    // Route paramétré APRÈS les routes spécifiques
    Route::get('/sales/{id}', function ($id) {
        try {
            $sale = DB::table('sales')
                ->leftJoin('customers', 'sales.customer_id', '=', 'customers.id')
                ->select(
                    'sales.*',
                    'customers.name as customer_name',
                    'customers.phone as customer_phone',
                    'customers.address as customer_address'
                )
                ->where('sales.id', $id)
                ->first();

            if (!$sale) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vente non trouvée'
                ], 404);
            }

            $items = DB::table('sale_items')
                ->join('products', 'sale_items.product_id', '=', 'products.id')
                ->where('sale_items.sale_id', $id)
                ->select(
                    'sale_items.*',
                    'products.name as product_name',
                    'products.sku as product_sku'
                )
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'sale' => $sale,
                    'items' => $items
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement de la vente',
                'error' => $e->getMessage()
            ], 500);
        }
    });
    
    // ====================================
    // STATISTIQUES DASHBOARD
    // ====================================
    
    Route::get('/stats', function () {
        try {
            $lowStockProducts = DB::table('products')
                ->whereRaw('stock <= min_stock')
                ->where('stock', '>', 0)
                ->get();

            $outOfStockProducts = DB::table('products')
                ->where('stock', 0)
                ->get();

            $stats = [
                'total_products' => DB::table('products')->count(),
                'low_stock_count' => $lowStockProducts->count(),
                'out_of_stock' => $outOfStockProducts->count(),
                'total_stock_value' => DB::table('products')
                    ->selectRaw('SUM(stock * unit_price) as value')
                    ->value('value') ?? 0,
                'total_customers' => DB::table('customers')->count(),
                'total_suppliers' => DB::table('suppliers')->count(),
                'alerts' => [
                    'low_stock' => $lowStockProducts,
                    'out_of_stock' => $outOfStockProducts
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement',
                'error' => $e->getMessage()
            ], 500);
        }
    });
    
    // ====================================
    // GESTION DU STOCK (Entrées/Sorties)
    // ====================================
    
    Route::post('/stock/in', function (Request $request) {
        try {
            $validated = $request->validate([
                'product_id' => 'required|exists:products,id',
                'quantity' => 'required|integer|min:1',
                'reason' => 'nullable|string'
            ]);
            
            DB::beginTransaction();
            
            $product = DB::table('products')->where('id', $validated['product_id'])->first();
            
            if (!$product) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Produit non trouvé'
                ], 404);
            }
            
            $previousStock = $product->stock;
            $newStock = $previousStock + $validated['quantity'];
            
            // Mettre à jour le stock
            DB::table('products')
                ->where('id', $validated['product_id'])
                ->update([
                    'stock' => $newStock,
                    'updated_at' => now()
                ]);
            
            // Enregistrer le mouvement
            DB::table('stock_movements')->insert([
                'product_id' => $validated['product_id'],
                'type' => 'in',
                'quantity' => $validated['quantity'],
                'previous_stock' => $previousStock,
                'new_stock' => $newStock,
                'reason' => $validated['reason'] ?? 'Réapprovisionnement',
                'user_id' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Stock ajouté avec succès',
                'data' => [
                    'previous_stock' => $previousStock,
                    'new_stock' => $newStock,
                    'quantity_added' => $validated['quantity']
                ]
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'ajout du stock',
                'error' => $e->getMessage()
            ], 500);
        }
    });
    
    Route::post('/stock/out', function (Request $request) {
        try {
            $validated = $request->validate([
                'product_id' => 'required|exists:products,id',
                'quantity' => 'required|integer|min:1',
                'reason' => 'nullable|string'
            ]);
            
            DB::beginTransaction();
            
            $product = DB::table('products')->where('id', $validated['product_id'])->first();
            
            if (!$product) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Produit non trouvé'
                ], 404);
            }
            
            if ($product->stock < $validated['quantity']) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Stock insuffisant'
                ], 400);
            }
            
            $previousStock = $product->stock;
            $newStock = $previousStock - $validated['quantity'];
            
            // Mettre à jour le stock
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
                'previous_stock' => $previousStock,
                'new_stock' => $newStock,
                'reason' => $validated['reason'] ?? 'Sortie de stock',
                'user_id' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Stock retiré avec succès',
                'data' => [
                    'previous_stock' => $previousStock,
                    'new_stock' => $newStock,
                    'quantity_removed' => $validated['quantity']
                ]
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du retrait du stock',
                'error' => $e->getMessage()
            ], 500);
        }
    });
    
    // ====================================
    // MOUVEMENTS DE STOCK
    // ====================================
    
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
            
            $movements = $query->limit(100)->get();
            
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
});

// ====================================
// ROUTE DE FALLBACK
// ====================================

Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'Route non trouvée',
    ], 404);
});
