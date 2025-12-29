<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Http\Request;
use App\Services\ProductSupplierService;
use Illuminate\Support\Facades\Validator;

class ProductSupplierController extends Controller
{
    protected ProductSupplierService $service;

    public function __construct(ProductSupplierService $service)
    {
        $this->service = $service;
    }

    /**
     * Liste les fournisseurs d'un produit
     */
    public function index(Product $product)
    {
        $suppliers = $product->suppliers->map(function ($supplier) {
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
    }

    /**
     * Associe un fournisseur à un produit
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
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $this->service->syncSuppliers($product->id, [
                [
                    'id' => $request->supplier_id,
                    'cost_price' => $request->cost_price ?? null,
                    'delivery_days' => $request->delivery_days ?? null,
                    'minimum_order_quantity' => $request->minimum_order_quantity ?? 1,
                    'is_preferred' => $request->is_preferred ?? false,
                    'notes' => $request->notes ?? null,
                ]
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Fournisseur associé avec succès'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'association',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Synchronise tous les fournisseurs d'un produit (remplace les existants)
     */
    public function sync(Request $request, Product $product)
    {
        $validator = Validator::make($request->all(), [
            'suppliers' => 'required|array',
            'suppliers.*.id' => 'required|exists:suppliers,id',
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
            $this->service->syncSuppliers($product->id, $request->suppliers);

            return response()->json([
                'success' => true,
                'message' => 'Fournisseurs synchronisés avec succès'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la synchronisation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Met à jour un fournisseur spécifique
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
            $this->service->syncSuppliers($product->id, [
                [
                    'id' => $supplier->id,
                    'cost_price' => $request->cost_price ?? null,
                    'delivery_days' => $request->delivery_days ?? null,
                    'minimum_order_quantity' => $request->minimum_order_quantity ?? 1,
                    'is_preferred' => $request->is_preferred ?? false,
                    'notes' => $request->notes ?? null,
                ]
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Association mise à jour avec succès'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Dissocie un fournisseur d'un produit
     */
    public function detach(Product $product, Supplier $supplier)
    {
        try {
            $this->service->syncSuppliers($product->id, collect($product->suppliers)
                ->reject(fn($s) => $s->id === $supplier->id)
                ->map(fn($s) => [
                    'id' => $s->id,
                    'cost_price' => $s->pivot->cost_price,
                    'delivery_days' => $s->pivot->delivery_days,
                    'minimum_order_quantity' => $s->pivot->minimum_order_quantity,
                    'is_preferred' => $s->pivot->is_preferred,
                    'notes' => $s->pivot->notes,
                ])->toArray()
            );

            return response()->json([
                'success' => true,
                'message' => 'Fournisseur dissocié avec succès'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la dissociation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Définit un fournisseur comme préféré
     */
    public function setPreferred(Product $product, Supplier $supplier)
    {
        try {
            $suppliers = $product->suppliers->map(function ($s) use ($supplier) {
                return [
                    'id' => $s->id,
                    'cost_price' => $s->pivot->cost_price,
                    'delivery_days' => $s->pivot->delivery_days,
                    'minimum_order_quantity' => $s->pivot->minimum_order_quantity,
                    'is_preferred' => $s->id === $supplier->id,
                    'notes' => $s->pivot->notes,
                ];
            })->toArray();

            $this->service->syncSuppliers($product->id, $suppliers);

            return response()->json([
                'success' => true,
                'message' => 'Fournisseur préféré défini avec succès'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la définition du fournisseur préféré',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Liste les produits d'un fournisseur
     */
    public function productsBySupplier(Supplier $supplier)
    {
        $products = $supplier->products->map(function ($product) {
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
    }
}
