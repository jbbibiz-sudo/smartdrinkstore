<?php
// Chemin: app/Http/Controllers/Api/ProductController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    /**
     * ✅ Liste tous les produits AVEC leurs unités et fournisseurs
     */
    public function index(Request $request)
    {
        try {
            $query = Product::with([
                'category', 
                'subcategory', 
                'suppliers',
                'baseUnit',      // ✅ NOUVEAU
                'retailUnit'     // ✅ NOUVEAU
            ])->orderBy('created_at', 'desc');

            // Filtres optionnels
            if ($request->has('category_id')) {
                $query->where('category_id', $request->category_id);
            }
            
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('sku', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%")
                      ->orWhere('barcode', 'like', "%{$search}%");
                });
            }

            $products = $query->get();

            // ✅ Ajouter les attributs calculés
            $products->each(function ($product) {
                $product->display_name = $product->display_name;
                $product->retail_unit_price = $product->retail_unit_price;
                $product->retail_cost_price = $product->retail_cost_price;
            });

            return response()->json([
                'success' => true,
                'data' => $products
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur chargement produits: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des produits',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ Affiche un produit avec ses unités
     */
    public function show($id)
    {
        try {
            $product = Product::with([
                'category', 
                'subcategory', 
                'suppliers',
                'baseUnit',
                'retailUnit'
            ])->findOrFail($id);

            // Ajouter les attributs calculés
            $product->display_name = $product->display_name;
            $product->retail_unit_price = $product->retail_unit_price;
            $product->retail_cost_price = $product->retail_cost_price;

            return response()->json([
                'success' => true,
                'data' => $product
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Produit non trouvé'
            ], 404);
        }
    }

    /**
     * Crée un nouveau produit
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'sku' => 'required|string|unique:products,sku',
                'code' => 'nullable|string',
                'barcode' => 'nullable|string',
                'category_id' => 'nullable|exists:categories,id',
                'subcategory_id' => 'nullable|exists:subcategories,id',
                'brand' => 'nullable|string',
                'volume' => 'nullable|string',
                'unit_price' => 'required|numeric|min:0',
                'cost_price' => 'nullable|numeric|min:0',
                'stock' => 'required|integer|min:0',
                'min_stock' => 'required|integer|min:0',
                'description' => 'nullable|string',
                'is_consigned' => 'boolean',
                'consignment_price' => 'nullable|numeric|min:0',
                'empty_containers_stock' => 'nullable|integer|min:0',
                // ✅ NOUVEAU : Validation des unités
                'base_unit_id' => 'nullable|exists:product_units,id',
                'base_unit_volume' => 'nullable|numeric|min:0',
                'base_unit_volume_unit' => 'nullable|string|in:L,ml,cl',
                'base_unit_quantity' => 'nullable|integer|min:1',
                'retail_unit_id' => 'nullable|exists:product_units,id',
                'suppliers' => 'nullable|array',
                'suppliers.*.id' => 'required|exists:suppliers,id',
                'suppliers.*.cost_price' => 'nullable|numeric|min:0',
                'suppliers.*.is_preferred' => 'boolean',
            ]);

            DB::beginTransaction();
            
            // Créer le produit
            $product = Product::create($validated);

            // Associer les fournisseurs si fournis
            if (!empty($validated['suppliers'])) {
                foreach ($validated['suppliers'] as $supplier) {
                    $product->suppliers()->attach($supplier['id'], [
                        'cost_price' => $supplier['cost_price'] ?? null,
                        'is_preferred' => $supplier['is_preferred'] ?? false,
                    ]);
                }
            }

            // Créer un mouvement de stock initial
            $product->addStock($validated['stock'], 'Stock initial');

            DB::commit();

            // Charger les relations
            $product->load(['suppliers', 'baseUnit', 'retailUnit']);
            $product->display_name = $product->display_name;

            return response()->json([
                'success' => true,
                'message' => 'Produit créé avec succès',
                'data' => $product
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
            Log::error('Erreur création produit: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ Met à jour un produit ET ses unités
     */
    public function update(Request $request, $id)
    {
        try {
            $product = Product::findOrFail($id);
            
            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'sku' => 'sometimes|required|string|unique:products,sku,' . $id,
                'code' => 'nullable|string',
                'barcode' => 'nullable|string',
                'category_id' => 'nullable|exists:categories,id',
                'subcategory_id' => 'nullable|exists:subcategories,id',
                'brand' => 'nullable|string',
                'volume' => 'nullable|string',
                'unit_price' => 'sometimes|required|numeric|min:0',
                'cost_price' => 'nullable|numeric|min:0',
                'stock' => 'sometimes|required|integer|min:0',
                'min_stock' => 'sometimes|required|integer|min:0',
                'description' => 'nullable|string',
                'is_consigned' => 'boolean',
                'consignment_price' => 'nullable|numeric|min:0',
                'empty_containers_stock' => 'nullable|integer|min:0',
                // ✅ NOUVEAU
                'base_unit_id' => 'nullable|exists:product_units,id',
                'base_unit_volume' => 'nullable|numeric|min:0',
                'base_unit_volume_unit' => 'nullable|string|in:L,ml,cl',
                'base_unit_quantity' => 'nullable|integer|min:1',
                'retail_unit_id' => 'nullable|exists:product_units,id',
                'suppliers' => 'nullable|array',
                'suppliers.*.id' => 'required|exists:suppliers,id',
                'suppliers.*.cost_price' => 'nullable|numeric|min:0',
                'suppliers.*.is_preferred' => 'boolean',
            ]);

            DB::beginTransaction();

            $product->update($validated);

            // Mettre à jour les fournisseurs si fournis
            if (isset($validated['suppliers'])) {
                $product->suppliers()->detach();
                
                foreach ($validated['suppliers'] as $supplier) {
                    $product->suppliers()->attach($supplier['id'], [
                        'cost_price' => $supplier['cost_price'] ?? null,
                        'is_preferred' => $supplier['is_preferred'] ?? false,
                    ]);
                }
            }

            DB::commit();

            $product->load(['suppliers', 'baseUnit', 'retailUnit']);
            $product->display_name = $product->display_name;

            return response()->json([
                'success' => true,
                'message' => 'Produit mis à jour avec succès',
                'data' => $product
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprime un produit
     */
    public function destroy($id)
    {
        try {
            $product = Product::findOrFail($id);
            $product->delete();

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
    }

    /**
     * Produits en stock faible
     */
    public function lowStock()
    {
        try {
            $products = Product::with(['category', 'suppliers', 'baseUnit', 'retailUnit'])
                ->lowStock()
                ->get();

            $products->each(function ($product) {
                $product->display_name = $product->display_name;
            });

            return response()->json([
                'success' => true,
                'data' => $products
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Produits en rupture de stock
     */
    public function outOfStock()
    {
        try {
            $products = Product::with(['category', 'suppliers', 'baseUnit', 'retailUnit'])
                ->outOfStock()
                ->get();

            $products->each(function ($product) {
                $product->display_name = $product->display_name;
            });

            return response()->json([
                'success' => true,
                'data' => $products
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Statistiques des produits
     */
    public function stats()
    {
        try {
            $stats = [
                'total' => Product::count(),
                'low_stock' => Product::lowStock()->count(),
                'out_of_stock' => Product::outOfStock()->count(),
                'total_value' => Product::selectRaw('SUM(stock * unit_price) as value')->value('value') ?? 0
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}