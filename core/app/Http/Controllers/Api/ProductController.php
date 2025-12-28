<?php
// app/Http/Controllers/Api/ProductController.php

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
     * ✅ Liste tous les produits AVEC leurs fournisseurs
     */
    public function index(Request $request)
    {
        try {
            $query = Product::with(['category', 'subcategory', 'suppliers'])
                ->orderBy('created_at', 'desc');

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
     * ✅ Affiche un produit avec ses fournisseurs
     */
    public function show($id)
    {
        try {
            $product = Product::with(['category', 'subcategory', 'suppliers'])
                ->findOrFail($id);

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

            return response()->json([
                'success' => true,
                'message' => 'Produit créé avec succès',
                'data' => $product->load('suppliers')
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
     * ✅ Met à jour un produit ET ses fournisseurs
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
                'suppliers' => 'nullable|array',
                'suppliers.*.id' => 'required|exists:suppliers,id',
                'suppliers.*.cost_price' => 'nullable|numeric|min:0',
                'suppliers.*.is_preferred' => 'boolean',
            ]);

            DB::beginTransaction();

            $product->update($validated);

            // Mettre à jour les fournisseurs si fournis
            if (isset($validated['suppliers'])) {
                // Détacher tous les anciens fournisseurs
                $product->suppliers()->detach();
                
                // Attacher les nouveaux
                foreach ($validated['suppliers'] as $supplier) {
                    $product->suppliers()->attach($supplier['id'], [
                        'cost_price' => $supplier['cost_price'] ?? null,
                        'is_preferred' => $supplier['is_preferred'] ?? false,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Produit mis à jour avec succès',
                'data' => $product->load('suppliers')
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
     * ✅ NOUVEAU : Associer/Dissocier un fournisseur
     */
    public function manageSupplier(Request $request, $productId)
    {
        try {
            $validated = $request->validate([
                'supplier_id' => 'required|exists:suppliers,id',
                'action' => 'required|in:attach,detach,update',
                'cost_price' => 'nullable|numeric|min:0',
                'delivery_days' => 'nullable|integer|min:0',
                'minimum_order_quantity' => 'nullable|integer|min:1',
                'is_preferred' => 'nullable|boolean',
                'notes' => 'nullable|string',
            ]);

            $product = Product::findOrFail($productId);
            $supplierId = $validated['supplier_id'];

            switch ($validated['action']) {
                case 'attach':
                    $product->suppliers()->attach($supplierId, [
                        'cost_price' => $validated['cost_price'] ?? null,
                        'delivery_days' => $validated['delivery_days'] ?? null,
                        'minimum_order_quantity' => $validated['minimum_order_quantity'] ?? 1,
                        'is_preferred' => $validated['is_preferred'] ?? false,
                        'notes' => $validated['notes'] ?? null,
                    ]);
                    $message = 'Fournisseur associé avec succès';
                    break;

                case 'detach':
                    $product->suppliers()->detach($supplierId);
                    $message = 'Fournisseur dissocié avec succès';
                    break;

                case 'update':
                    $product->suppliers()->updateExistingPivot($supplierId, [
                        'cost_price' => $validated['cost_price'] ?? null,
                        'delivery_days' => $validated['delivery_days'] ?? null,
                        'minimum_order_quantity' => $validated['minimum_order_quantity'] ?? 1,
                        'is_preferred' => $validated['is_preferred'] ?? false,
                        'notes' => $validated['notes'] ?? null,
                    ]);
                    $message = 'Informations fournisseur mises à jour';
                    break;
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $product->load('suppliers')
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur gestion fournisseur produit: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la gestion du fournisseur',
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
            $products = Product::with(['category', 'suppliers'])
                ->lowStock()
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
            $products = Product::with(['category', 'suppliers'])
                ->outOfStock()
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