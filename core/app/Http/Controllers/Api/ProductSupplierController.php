<?php
// app/Http/Controllers/Api/ProductSupplierController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Contrôleur dédié à la gestion des relations Produit-Fournisseur
 * 
 * Responsabilités :
 * - Associer/Dissocier des fournisseurs aux produits
 * - Gérer les informations pivot (prix, délais, etc.)
 * - Lister les fournisseurs d'un produit
 * - Lister les produits d'un fournisseur
 */
class ProductSupplierController extends Controller
{
    /**
     * Liste tous les fournisseurs d'un produit
     * 
     * @param int $productId
     * @return \Illuminate\Http\JsonResponse
     */
    public function index($productId)
    {
        try {
            $product = Product::findOrFail($productId);
            $suppliers = $product->suppliers()->get();

            return response()->json([
                'success' => true,
                'data' => $suppliers
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur liste fournisseurs produit:', [
                'product_id' => $productId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des fournisseurs'
            ], 500);
        }
    }

    /**
     * Associe un fournisseur à un produit
     * 
     * @param Request $request
     * @param int $productId
     * @return \Illuminate\Http\JsonResponse
     */
    public function attach(Request $request, $productId)
    {
        try {
            $validated = $request->validate([
                'supplier_id' => 'required|exists:suppliers,id',
                'cost_price' => 'nullable|numeric|min:0',
                'delivery_days' => 'nullable|integer|min:0',
                'minimum_order_quantity' => 'nullable|integer|min:1',
                'is_preferred' => 'nullable|boolean',
                'notes' => 'nullable|string|max:1000',
            ]);

            $product = Product::findOrFail($productId);
            $supplierId = $validated['supplier_id'];

            // Vérifier si l'association existe déjà
            if ($product->suppliers()->where('supplier_id', $supplierId)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce fournisseur est déjà associé à ce produit'
                ], 422);
            }

            DB::beginTransaction();

            // Si c'est un fournisseur préféré, retirer le statut des autres
            if ($validated['is_preferred'] ?? false) {
                $product->suppliers()->updateExistingPivot(
                    $product->suppliers()->pluck('suppliers.id'),
                    ['is_preferred' => false]
                );
            }

            // Associer le fournisseur
            $product->suppliers()->attach($supplierId, [
                'cost_price' => $validated['cost_price'] ?? null,
                'delivery_days' => $validated['delivery_days'] ?? null,
                'minimum_order_quantity' => $validated['minimum_order_quantity'] ?? 1,
                'is_preferred' => $validated['is_preferred'] ?? false,
                'notes' => $validated['notes'] ?? null,
            ]);

            DB::commit();

            // Charger le fournisseur avec les données pivot
            $supplier = $product->suppliers()
                ->where('supplier_id', $supplierId)
                ->first();

            Log::info('Fournisseur associé au produit', [
                'product_id' => $productId,
                'supplier_id' => $supplierId
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Fournisseur associé avec succès',
                'data' => $supplier
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur association fournisseur:', [
                'product_id' => $productId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'association du fournisseur',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Met à jour les informations d'un fournisseur pour un produit
     * 
     * @param Request $request
     * @param int $productId
     * @param int $supplierId
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $productId, $supplierId)
    {
        try {
            $validated = $request->validate([
                'cost_price' => 'nullable|numeric|min:0',
                'delivery_days' => 'nullable|integer|min:0',
                'minimum_order_quantity' => 'nullable|integer|min:1',
                'is_preferred' => 'nullable|boolean',
                'notes' => 'nullable|string|max:1000',
            ]);

            $product = Product::findOrFail($productId);
            $supplier = Supplier::findOrFail($supplierId);

            // Vérifier que l'association existe
            if (!$product->suppliers()->where('supplier_id', $supplierId)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce fournisseur n\'est pas associé à ce produit'
                ], 404);
            }

            DB::beginTransaction();

            // Si c'est un fournisseur préféré, retirer le statut des autres
            if ($validated['is_preferred'] ?? false) {
                $product->suppliers()
                    ->wherePivot('supplier_id', '!=', $supplierId)
                    ->updateExistingPivot(
                        $product->suppliers()
                            ->wherePivot('supplier_id', '!=', $supplierId)
                            ->pluck('suppliers.id'),
                        ['is_preferred' => false]
                    );
            }

            // Mettre à jour les informations
            $product->suppliers()->updateExistingPivot($supplierId, $validated);

            DB::commit();

            // Recharger le fournisseur avec les nouvelles données
            $updatedSupplier = $product->suppliers()
                ->where('supplier_id', $supplierId)
                ->first();

            Log::info('Informations fournisseur mises à jour', [
                'product_id' => $productId,
                'supplier_id' => $supplierId
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Informations mises à jour avec succès',
                'data' => $updatedSupplier
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur mise à jour fournisseur:', [
                'product_id' => $productId,
                'supplier_id' => $supplierId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Dissocie un fournisseur d'un produit
     * 
     * @param int $productId
     * @param int $supplierId
     * @return \Illuminate\Http\JsonResponse
     */
    public function detach($productId, $supplierId)
    {
        try {
            $product = Product::findOrFail($productId);
            $supplier = Supplier::findOrFail($supplierId);

            // Vérifier que l'association existe
            if (!$product->suppliers()->where('supplier_id', $supplierId)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce fournisseur n\'est pas associé à ce produit'
                ], 404);
            }

            // Dissocier
            $product->suppliers()->detach($supplierId);

            Log::info('Fournisseur dissocié du produit', [
                'product_id' => $productId,
                'supplier_id' => $supplierId
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Fournisseur dissocié avec succès'
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur dissociation fournisseur:', [
                'product_id' => $productId,
                'supplier_id' => $supplierId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la dissociation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Définit un fournisseur comme préféré pour un produit
     * 
     * @param int $productId
     * @param int $supplierId
     * @return \Illuminate\Http\JsonResponse
     */
    public function setPreferred($productId, $supplierId)
    {
        try {
            $product = Product::findOrFail($productId);
            $supplier = Supplier::findOrFail($supplierId);

            if (!$product->suppliers()->where('supplier_id', $supplierId)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce fournisseur n\'est pas associé à ce produit'
                ], 404);
            }

            DB::beginTransaction();

            // Retirer le statut préféré de tous les autres
            $product->suppliers()->updateExistingPivot(
                $product->suppliers()->pluck('suppliers.id'),
                ['is_preferred' => false]
            );

            // Définir comme préféré
            $product->suppliers()->updateExistingPivot($supplierId, [
                'is_preferred' => true
            ]);

            DB::commit();

            Log::info('Fournisseur défini comme préféré', [
                'product_id' => $productId,
                'supplier_id' => $supplierId
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Fournisseur défini comme préféré'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur définition fournisseur préféré:', [
                'product_id' => $productId,
                'supplier_id' => $supplierId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la définition du fournisseur préféré',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Liste tous les produits d'un fournisseur
     * 
     * @param int $supplierId
     * @return \Illuminate\Http\JsonResponse
     */
    public function productsBySupplier($supplierId)
    {
        try {
            $supplier = Supplier::findOrFail($supplierId);
            $products = $supplier->products()
                ->with(['category', 'subcategory'])
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'supplier' => $supplier,
                    'products' => $products,
                    'total_products' => $products->count()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur liste produits fournisseur:', [
                'supplier_id' => $supplierId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des produits'
            ], 500);
        }
    }

    /**
     * Synchronise les fournisseurs d'un produit
     * (remplace tous les fournisseurs existants)
     * 
     * @param Request $request
     * @param int $productId
     * @return \Illuminate\Http\JsonResponse
     */
    public function sync(Request $request, $productId)
    {
        try {
            $validated = $request->validate([
                'suppliers' => 'required|array',
                'suppliers.*.id' => 'required|exists:suppliers,id',
                'suppliers.*.cost_price' => 'nullable|numeric|min:0',
                'suppliers.*.delivery_days' => 'nullable|integer|min:0',
                'suppliers.*.minimum_order_quantity' => 'nullable|integer|min:1',
                'suppliers.*.is_preferred' => 'nullable|boolean',
                'suppliers.*.notes' => 'nullable|string|max:1000',
            ]);

            $product = Product::findOrFail($productId);

            DB::beginTransaction();

            // Préparer les données pour la synchronisation
            $syncData = [];
            foreach ($validated['suppliers'] as $supplier) {
                $syncData[$supplier['id']] = [
                    'cost_price' => $supplier['cost_price'] ?? null,
                    'delivery_days' => $supplier['delivery_days'] ?? null,
                    'minimum_order_quantity' => $supplier['minimum_order_quantity'] ?? 1,
                    'is_preferred' => $supplier['is_preferred'] ?? false,
                    'notes' => $supplier['notes'] ?? null,
                ];
            }

            // Synchroniser (remplace tout)
            $product->suppliers()->sync($syncData);

            DB::commit();

            $updatedSuppliers = $product->suppliers()->get();

            Log::info('Fournisseurs synchronisés', [
                'product_id' => $productId,
                'suppliers_count' => count($syncData)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Fournisseurs synchronisés avec succès',
                'data' => $updatedSuppliers
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur synchronisation fournisseurs:', [
                'product_id' => $productId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la synchronisation',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}