<?php
// Chemin: C:\smartdrinkstore\core\app\Http\Controllers\Api\ProductController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    /**
     * Liste tous les produits
     * ✅ FORMAT CORRIGÉ - Retourne {success: true, data: [...]}
     */
    public function index(Request $request)
    {
        try {
            $query = DB::table('products')
                ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
                ->leftJoin('subcategories', 'products.subcategory_id', '=', 'subcategories.id')
                ->select(
                    'products.*',
                    'categories.name as category_name',
                    'subcategories.name as subcategory_name'
                )
                ->orderBy('products.created_at', 'desc');

            // Filtres optionnels
            if ($request->has('category_id')) {
                $query->where('products.category_id', $request->category_id);
            }
            
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('products.name', 'like', "%{$search}%")
                      ->orWhere('products.sku', 'like', "%{$search}%")
                      ->orWhere('products.code', 'like', "%{$search}%")
                      ->orWhere('products.barcode', 'like', "%{$search}%");
                });
            }

            // ✅ CORRECTION: Ne pas paginer, retourner tous les produits
            $products = $query->get();

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
     * Affiche un produit spécifique
     */
    public function show($id)
    {
        try {
            $product = DB::table('products')
                ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
                ->leftJoin('subcategories', 'products.subcategory_id', '=', 'subcategories.id')
                ->select(
                    'products.*',
                    'categories.name as category_name',
                    'subcategories.name as subcategory_name'
                )
                ->where('products.id', $id)
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
                'message' => 'Erreur',
                'error' => $e->getMessage()
            ], 500);
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
            ]);

            $productId = DB::table('products')->insertGetId(array_merge($validated, [
                'created_at' => now(),
                'updated_at' => now()
            ]));

            // Créer un mouvement de stock initial
            DB::table('stock_movements')->insert([
                'product_id' => $productId,
                'type' => 'in',
                'quantity' => $validated['stock'],
                'previous_stock' => 0,
                'new_stock' => $validated['stock'],
                'reason' => 'Stock initial',
                'user_id' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            $product = DB::table('products')->where('id', $productId)->first();

            return response()->json([
                'success' => true,
                'message' => 'Produit créé avec succès',
                'data' => $product
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Erreur création produit: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Met à jour un produit
     */
    public function update(Request $request, $id)
    {
        try {
            $product = DB::table('products')->where('id', $id)->first();
            
            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Produit non trouvé'
                ], 404);
            }

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
            ]);

            DB::table('products')
                ->where('id', $id)
                ->update(array_merge($validated, [
                    'updated_at' => now()
                ]));

            $updatedProduct = DB::table('products')->where('id', $id)->first();

            return response()->json([
                'success' => true,
                'message' => 'Produit mis à jour avec succès',
                'data' => $updatedProduct
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
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
            $product = DB::table('products')->where('id', $id)->first();
            
            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Produit non trouvé'
                ], 404);
            }

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
    }

    /**
     * Produits en stock faible
     */
    public function lowStock()
    {
        try {
            $products = DB::table('products')
                ->whereRaw('stock <= min_stock')
                ->where('stock', '>', 0)
                ->get();

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
            $products = DB::table('products')
                ->where('stock', 0)
                ->get();

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
                'total' => DB::table('products')->count(),
                'low_stock' => DB::table('products')->whereRaw('stock <= min_stock')->where('stock', '>', 0)->count(),
                'out_of_stock' => DB::table('products')->where('stock', 0)->count(),
                'total_value' => DB::table('products')->selectRaw('SUM(stock * unit_price) as value')->value('value') ?? 0
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