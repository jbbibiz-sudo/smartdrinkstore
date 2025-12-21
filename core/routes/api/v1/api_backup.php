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
                    'code' => $product->code ?? $product->sku,
                    'unit_price' => (float) $product->unit_price,
                    'stock' => (int) $product->stock,
                    'min_stock' => (int) $product->min_stock,
                    'category_id' => $product->category_id,
                    'subcategory_id' => $product->subcategory_id,
                    'description' => $product->description ?? '',
                    'image' => $product->image ?? null,
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
                'name' => 'required|string|max:255',
                'sku' => 'required|string|max:50|unique:products',
                'code' => 'nullable|string|max:50',
                'unit_price' => 'required|numeric|min:0',
                'stock' => 'required|integer|min:0',
                'min_stock' => 'required|integer|min:0',
                'category_id' => 'nullable|exists:categories,id',
                'subcategory_id' => 'nullable|exists:subcategories,id',
                'image' => 'nullable|string',
                'description' => 'nullable|string'
            ]);
            
            $id = DB::table('products')->insertGetId([
                'name' => $validated['name'],
                'sku' => $validated['sku'],
                'code' => $validated['code'] ?? $validated['sku'],
                'unit_price' => $validated['unit_price'],
                'stock' => $validated['stock'],
                'min_stock' => $validated['min_stock'],
                'category_id' => $validated['category_id'] ?? null,
                'subcategory_id' => $validated['subcategory_id'] ?? null,
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
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du produit',
                'error' => $e->getMessage()
            ], 500);
        }
    });
    
    Route::put('/products/{id}', function (Request $request, $id) {
        try {
            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'sku' => 'sometimes|required|string|max:50|unique:products,sku,' . $id,
                'code' => 'nullable|string|max:50',
                'unit_price' => 'sometimes|required|numeric|min:0',
                'stock' => 'sometimes|required|integer|min:0',
                'min_stock' => 'sometimes|required|integer|min:0',
                'category_id' => 'nullable|exists:categories,id',
                'subcategory_id' => 'nullable|exists:subcategories,id',
                'image' => 'nullable|string',
                'description' => 'nullable|string'
            ]);
            
            DB::table('products')
                ->where('id', $id)
                ->update(array_merge($validated, [
                    'updated_at' => now()
                ]));
            
            $product = DB::table('products')->where('id', $id)->first();
            
            return response()->json([
                'success' => true,
                'message' => 'Produit mis à jour avec succès',
                'data' => $product
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du produit',
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
                'message' => 'Erreur lors de la suppression du produit',
                'error' => $e->getMessage()
            ], 500);
        }
    });
    
    // ====================================
    // ALERTES DE STOCK
    // ====================================
    
    Route::get('/products/alerts', function () {
        try {
            $lowStock = DB::table('products')
                ->whereColumn('stock', '<=', 'min_stock')
                ->where('stock', '>', 0)
                ->orderBy('stock', 'asc')
                ->get();
            
            $outOfStock = DB::table('products')
                ->where('stock', '=', 0)
                ->get();
            
            $alerts = [
                'low_stock' => $lowStock,
                'out_of_stock' => $outOfStock,
                'count' => $lowStock->count() + $outOfStock->count()
            ];
            
            return response()->json([
                'success' => true,
                'data' => $alerts
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
    // MOUVEMENTS DE STOCK
    // ====================================
    
    Route::get('/stock/movements', function (Request $request) {
        try {
            $query = DB::table('stock_movements')
                ->join('products', 'stock_movements.product_id', '=', 'products.id')
                ->select(
                    'stock_movements.*',
                    'products.name as product_name',
                    'products.sku as product_sku'
                )
                ->orderBy('stock_movements.created_at', 'desc');
            
            if ($request->has('type') && $request->type != '') {
                $query->where('stock_movements.type', $request->type);
            }
            
            if ($request->has('product_id') && $request->product_id != '') {
                $query->where('stock_movements.product_id', $request->product_id);
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
    
    Route::post('/stock/in', function (Request $request) {
        try {
            $validated = $request->validate([
                'product_id' => 'required|exists:products,id',
                'quantity' => 'required|integer|min:1',
                'reason' => 'required|string|max:255'
            ]);
            
            DB::beginTransaction();
            
            // Mettre à jour le stock
            DB::table('products')
                ->where('id', $validated['product_id'])
                ->increment('stock', $validated['quantity']);
            
            // Enregistrer le mouvement
            DB::table('stock_movements')->insert([
                'product_id' => $validated['product_id'],
                'type' => 'in',
                'quantity' => $validated['quantity'],
                'reason' => $validated['reason'],
                'created_at' => now()
            ]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Stock ajouté avec succès'
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
                'reason' => 'required|string|max:255'
            ]);
            
            DB::beginTransaction();
            
            // Vérifier le stock disponible
            $product = DB::table('products')->where('id', $validated['product_id'])->first();
            
            if ($product->stock < $validated['quantity']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock insuffisant'
                ], 400);
            }
            
            // Mettre à jour le stock
            DB::table('products')
                ->where('id', $validated['product_id'])
                ->decrement('stock', $validated['quantity']);
            
            // Enregistrer le mouvement
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
                'message' => 'Stock retiré avec succès'
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
    // CLIENTS
    // ====================================
    
    Route::get('/customers', function () {
        try {
            $customers = DB::table('customers')
                ->orderBy('name')
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $customers
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des clients',
                'error' => $e->getMessage()
            ], 500);
        }
    });
    
    Route::post('/customers', function (Request $request) {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'phone' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:255',
                'address' => 'nullable|string'
            ]);
            
            $id = DB::table('customers')->insertGetId(array_merge($validated, [
                'balance' => 0,
                'created_at' => now(),
                'updated_at' => now()
            ]));
            
            $customer = DB::table('customers')->find($id);
            
            return response()->json([
                'success' => true,
                'message' => 'Client créé avec succès',
                'data' => $customer
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du client',
                'error' => $e->getMessage()
            ], 500);
        }
    });
    
    Route::put('/customers/{id}', function (Request $request, $id) {
        try {
            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'phone' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:255',
                'address' => 'nullable|string'
            ]);
            
            DB::table('customers')
                ->where('id', $id)
                ->update(array_merge($validated, [
                    'updated_at' => now()
                ]));
            
            $updatedCustomer = DB::table('customers')->find($id);
            
            return response()->json([
                'success' => true,
                'message' => 'Client mis à jour avec succès',
                'data' => $updatedCustomer
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du client',
                'error' => $e->getMessage()
            ], 500);
        }
    });
    
    Route::delete('/customers/{id}', function ($id) {
        try {
            $customer = DB::table('customers')->find($id);
            
            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Client non trouvé'
                ], 404);
            }
            
            DB::table('customers')->where('id', $id)->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Client supprimé avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du client',
                'error' => $e->getMessage()
            ], 500);
        }
    });
    
    // ====================================
    // FOURNISSEURS
    // ====================================
    
    Route::get('/suppliers', function () {
        try {
            $suppliers = DB::table('suppliers')
                ->orderBy('name')
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $suppliers
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des fournisseurs',
                'error' => $e->getMessage()
            ], 500);
        }
    });
    
    Route::post('/suppliers', function (Request $request) {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'phone' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:255',
                'address' => 'nullable|string'
            ]);
            
            $id = DB::table('suppliers')->insertGetId(array_merge($validated, [
                'created_at' => now(),
                'updated_at' => now()
            ]));
            
            $supplier = DB::table('suppliers')->find($id);
            
            return response()->json([
                'success' => true,
                'message' => 'Fournisseur créé avec succès',
                'data' => $supplier
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du fournisseur',
                'error' => $e->getMessage()
            ], 500);
        }
    });
    
    Route::put('/suppliers/{id}', function (Request $request, $id) {
        try {
            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'phone' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:255',
                'address' => 'nullable|string'
            ]);
            
            DB::table('suppliers')
                ->where('id', $id)
                ->update(array_merge($validated, [
                    'updated_at' => now()
                ]));
            
            $updatedSupplier = DB::table('suppliers')->find($id);
            
            return response()->json([
                'success' => true,
                'message' => 'Fournisseur mis à jour avec succès',
                'data' => $updatedSupplier
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du fournisseur',
                'error' => $e->getMessage()
            ], 500);
        }
    });
    
    Route::delete('/suppliers/{id}', function ($id) {
        try {
            DB::table('suppliers')->where('id', $id)->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Fournisseur supprimé avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du fournisseur',
                'error' => $e->getMessage()
            ], 500);
        }
    });
    
    // ====================================
    // DASHBOARD
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
                'message' => 'Erreur',
                'error' => $e->getMessage()
            ], 500);
        }
    });
    
    // ====================================
    // VENTES
    // ====================================
    
    Route::post('/sales', function (Request $request) {
        try {
            $validated = $request->validate([
                'customer_id' => 'nullable|exists:customers,id',
                'payment_method' => 'required|in:cash,credit,mobile',
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.unit_price' => 'required|numeric|min:0',
                'items.*.subtotal' => 'required|numeric|min:0',
                'total_amount' => 'required|numeric|min:0',
                'discount' => 'nullable|numeric|min:0'
            ]);
            
            DB::beginTransaction();
            
            // Créer la vente
            $saleId = DB::table('sales')->insertGetId([
                'customer_id' => $validated['customer_id'],
                'payment_method' => $validated['payment_method'],
                'total_amount' => $validated['total_amount'],
                'discount' => $validated['discount'] ?? 0,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            // Ajouter les items et mettre à jour le stock
            foreach ($validated['items'] as $item) {
                // Vérifier le stock
                $product = DB::table('products')->where('id', $item['product_id'])->first();
                
                if ($product->stock < $item['quantity']) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "Stock insuffisant pour {$product->name}"
                    ], 400);
                }
                
                // Créer la ligne de vente
                DB::table('sale_items')->insert([
                    'sale_id' => $saleId,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['subtotal'],
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                
                // Décrémenter le stock
                DB::table('products')
                    ->where('id', $item['product_id'])
                    ->decrement('stock', $item['quantity']);
                
                // Enregistrer le mouvement de stock
                DB::table('stock_movements')->insert([
                    'product_id' => $item['product_id'],
                    'type' => 'out',
                    'quantity' => $item['quantity'],
                    'reason' => "Vente #$saleId",
                    'created_at' => now()
                ]);
            }
            
            // Si vente à crédit, mettre à jour le solde client
            if ($validated['payment_method'] === 'credit' && $validated['customer_id']) {
                DB::table('customers')
                    ->where('id', $validated['customer_id'])
                    ->increment('balance', $validated['total_amount']);
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Vente enregistrée avec succès',
                'sale_id' => $saleId
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'enregistrement de la vente',
                'error' => $e->getMessage()
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
});
