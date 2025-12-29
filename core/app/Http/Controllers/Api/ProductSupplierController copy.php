<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ProductSupplierController extends Controller
{
    /**
     * Liste les fournisseurs d'un produit
     * GET /api/v1/products/{product}/suppliers
     * 
     * @param  Product  $product
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Product $product)
    {
        try {
            $suppliers = $product->suppliers->map(function($supplier) {
                return [
                    'id' => $supplier->id,
                    'name' => $supplier->name,
                    'phone' => $supplier->phone,
                    'email' => $supplier->email,
                    'address' => $supplier->address,
                    'cost_price' => $supplier->pivot->cost_price,
                    'delivery_days' => $supplier->pivot->delivery_days,
                    'minimum_order_quantity' => $supplier->pivot->minimum_order_quantity,
                    'is_preferred' => $supplier->pivot->is_preferred,
                    'notes' => $supplier->pivot->notes,
                    'created_at' => $supplier->pivot->created_at,
                    'updated_at' => $supplier->pivot->updated_at,
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => $suppliers,
                'message' => 'Fournisseurs du produit récupérés avec succès'
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des fournisseurs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Associe un fournisseur à un produit
     * POST /api/v1/products/{product}/suppliers
     * 
     * @param  Request  $request
     * @param  Product  $product
     * @return \Illuminate\Http\JsonResponse
     */
    public function attach(Request $request, Product $product)
    {
        $validator = Validator::make($request->all(), [
            'supplier_id' => 'required|exists:suppliers,id',
            'cost_price' => 'nullable|numeric|min:0',
            'delivery_days' => 'nullable|integer|min:0',
            'minimum_order_quantity' => 'nullable|integer|min:1',
            'is_preferred' => 'boolean',
            'notes' => 'nullable|string|max:1000',
        ], [
            'supplier_id.required' => 'Le fournisseur est obligatoire',
            'supplier_id.exists' => 'Le fournisseur sélectionné n\'existe pas',
            'cost_price.numeric' => 'Le prix d\'achat doit être un nombre',
            'cost_price.min' => 'Le prix d\'achat doit être positif',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Vérifier si l'association existe déjà
            if ($product->suppliers()->where('supplier_id', $request->supplier_id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce fournisseur est déjà associé à ce produit'
                ], 400);
            }
            
            DB::beginTransaction();
            
            // Si c'est le fournisseur préféré, retirer la préférence des autres
            if ($request->is_preferred) {
                $product->suppliers()->update(['is_preferred' => false]);
            }
            
            // Associer le fournisseur au produit
            $product->suppliers()->attach($request->supplier_id, [
                'cost_price' => $request->cost_price,
                'delivery_days' => $request->delivery_days,
                'minimum_order_quantity' => $request->minimum_order_quantity ?? 1,
                'is_preferred' => $request->is_preferred ?? false,
                'notes' => $request->notes,
            ]);
            
            DB::commit();
            
            // Récupérer les données complètes du fournisseur ajouté
            $supplier = $product->suppliers()->where('supplier_id', $request->supplier_id)->first();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $supplier->id,
                    'name' => $supplier->name,
                    'phone' => $supplier->phone,
                    'email' => $supplier->email,
                    'cost_price' => $supplier->pivot->cost_price,
                    'delivery_days' => $supplier->pivot->delivery_days,
                    'minimum_order_quantity' => $supplier->pivot->minimum_order_quantity,
                    'is_preferred' => $supplier->pivot->is_preferred,
                    'notes' => $supplier->pivot->notes,
                ],
                'message' => 'Fournisseur associé avec succès'
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'association',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Synchronise tous les fournisseurs d'un produit (remplace)
     * PUT /api/v1/products/{product}/suppliers
     * 
     * @param  Request  $request
     * @param  Product  $product
     * @return \Illuminate\Http\JsonResponse
     */
    public function sync(Request $request, Product $product)
    {
        $validator = Validator::make($request->all(), [
            'suppliers' => 'required|array',
            'suppliers.*.supplier_id' => 'required|exists:suppliers,id',
            'suppliers.*.cost_price' => 'nullable|numeric|min:0',
            'suppliers.*.delivery_days' => 'nullable|integer|min:0',
            'suppliers.*.minimum_order_quantity' => 'nullable|integer|min:1',
            'suppliers.*.is_preferred' => 'boolean',
            'suppliers.*.notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();
            
            $syncData = [];
            foreach ($request->suppliers as $supplierData) {
                $syncData[$supplierData['supplier_id']] = [
                    'cost_price' => $supplierData['cost_price'] ?? null,
                    'delivery_days' => $supplierData['delivery_days'] ?? null,
                    'minimum_order_quantity' => $supplierData['minimum_order_quantity'] ?? 1,
                    'is_preferred' => $supplierData['is_preferred'] ?? false,
                    'notes' => $supplierData['notes'] ?? null,
                ];
            }
            
            // Sync remplace toutes les associations existantes
            $product->suppliers()->sync($syncData);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Fournisseurs synchronisés avec succès'
            ], 200);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la synchronisation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Met à jour les informations d'un fournisseur spécifique
     * PUT /api/v1/products/{product}/suppliers/{supplier}
     * 
     * @param  Request  $request
     * @param  Product  $product
     * @param  Supplier  $supplier
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Product $product, Supplier $supplier)
    {
        $validator = Validator::make($request->all(), [
            'cost_price' => 'nullable|numeric|min:0',
            'delivery_days' => 'nullable|integer|min:0',
            'minimum_order_quantity' => 'nullable|integer|min:1',
            'is_preferred' => 'boolean',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Vérifier si l'association existe
            if (!$product->suppliers()->where('supplier_id', $supplier->id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette association n\'existe pas'
                ], 404);
            }
            
            DB::beginTransaction();
            
            // Si on définit comme préféré, retirer la préférence des autres
            if ($request->has('is_preferred') && $request->is_preferred) {
                $product->suppliers()
                    ->wherePivot('supplier_id', '!=', $supplier->id)
                    ->update(['is_preferred' => false]);
            }
            
            // Mettre à jour l'association
            $updateData = [];
            if ($request->has('cost_price')) $updateData['cost_price'] = $request->cost_price;
            if ($request->has('delivery_days')) $updateData['delivery_days'] = $request->delivery_days;
            if ($request->has('minimum_order_quantity')) $updateData['minimum_order_quantity'] = $request->minimum_order_quantity;
            if ($request->has('is_preferred')) $updateData['is_preferred'] = $request->is_preferred;
            if ($request->has('notes')) $updateData['notes'] = $request->notes;
            
            $product->suppliers()->updateExistingPivot($supplier->id, $updateData);
            
            DB::commit();
            
            // Récupérer les données mises à jour
            $updatedSupplier = $product->suppliers()->where('supplier_id', $supplier->id)->first();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $updatedSupplier->id,
                    'name' => $updatedSupplier->name,
                    'cost_price' => $updatedSupplier->pivot->cost_price,
                    'delivery_days' => $updatedSupplier->pivot->delivery_days,
                    'minimum_order_quantity' => $updatedSupplier->pivot->minimum_order_quantity,
                    'is_preferred' => $updatedSupplier->pivot->is_preferred,
                    'notes' => $updatedSupplier->pivot->notes,
                ],
                'message' => 'Association mise à jour avec succès'
            ], 200);
            
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
     * Dissocie un fournisseur d'un produit
     * DELETE /api/v1/products/{product}/suppliers/{supplier}
     * 
     * @param  Product  $product
     * @param  Supplier  $supplier
     * @return \Illuminate\Http\JsonResponse
     */
    public function detach(Product $product, Supplier $supplier)
    {
        try {
            // Vérifier si l'association existe
            if (!$product->suppliers()->where('supplier_id', $supplier->id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette association n\'existe pas'
                ], 404);
            }
            
            DB::beginTransaction();
            
            // Dissocier le fournisseur
            $product->suppliers()->detach($supplier->id);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Fournisseur dissocié avec succès'
            ], 200);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la dissociation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Définit un fournisseur comme préféré pour un produit
     * PATCH /api/v1/products/{product}/suppliers/{supplier}/preferred
     * 
     * @param  Product  $product
     * @param  Supplier  $supplier
     * @return \Illuminate\Http\JsonResponse
     */
    public function setPreferred(Product $product, Supplier $supplier)
    {
        try {
            // Vérifier si l'association existe
            if (!$product->suppliers()->where('supplier_id', $supplier->id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette association n\'existe pas'
                ], 404);
            }
            
            DB::beginTransaction();
            
            // Retirer la préférence de tous les fournisseurs
            $product->suppliers()->update(['is_preferred' => false]);
            
            // Définir le nouveau fournisseur préféré
            $product->suppliers()->updateExistingPivot($supplier->id, [
                'is_preferred' => true
            ]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Fournisseur préféré défini avec succès'
            ], 200);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la définition du fournisseur préféré',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Liste les produits d'un fournisseur
     * GET /api/v1/suppliers/{supplier}/products
     * 
     * @param  Supplier  $supplier
     * @return \Illuminate\Http\JsonResponse
     */
    public function productsBySupplier(Supplier $supplier)
    {
        try {
            $products = $supplier->products->map(function($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'unit_price' => $product->unit_price,
                    'stock' => $product->stock,
                    'cost_price' => $product->pivot->cost_price,
                    'delivery_days' => $product->pivot->delivery_days,
                    'minimum_order_quantity' => $product->pivot->minimum_order_quantity,
                    'is_preferred' => $product->pivot->is_preferred,
                    'notes' => $product->pivot->notes,
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => $products,
                'message' => 'Produits du fournisseur récupérés avec succès'
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des produits',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
