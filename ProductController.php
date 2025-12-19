<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    /**
     * Display a listing of the products.
     */
    public function index()
    {
        try {
            $products = DB::table('products')
                ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
                ->select(
                    'products.*',
                    'categories.name as category_name'
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
                    'category' => [
                        'id' => $product->category_id,
                        'name' => $product->category_name ?? 'N/A'
                    ]
                ];
            });

            return response()->json(['success' => true, 'data' => $formatted]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erreur lors du chargement des produits', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Store a newly created product in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'sku' => 'required|string|max:50|unique:products',
                'unit_price' => 'required|numeric|min:0',
                'stock' => 'required|integer|min:0',
                'min_stock' => 'required|integer|min:0',
                'category_id' => 'nullable|exists:categories,id'
            ]);

            $id = DB::table('products')->insertGetId(
                array_merge($validated, [
                    'category_id' => $validated['category_id'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now()
                ])
            );

            $product = DB::table('products')->where('id', $id)->first();

            return response()->json(['success' => true, 'message' => 'Produit créé avec succès', 'data' => $product], 201);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validation échouée', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erreur lors de la création', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update the specified product in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            $product = DB::table('products')->where('id', $id)->first();
            if (!$product) {
                return response()->json(['success' => false, 'message' => 'Produit non trouvé'], 404);
            }

            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'sku' => 'sometimes|required|string|max:50|unique:products,sku,' . $id,
                'unit_price' => 'sometimes|required|numeric|min:0',
                'stock' => 'sometimes|required|integer|min:0',
                'min_stock' => 'sometimes|required|integer|min:0',
                'category_id' => 'nullable|exists:categories,id'
            ]);

            $validated['updated_at'] = now();

            DB::table('products')->where('id', $id)->update($validated);

            $updatedProduct = DB::table('products')->where('id', $id)->first();

            return response()->json(['success' => true, 'message' => 'Produit mis à jour avec succès', 'data' => $updatedProduct]);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validation échouée', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erreur lors de la mise à jour', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified product from storage.
     */
    public function destroy($id)
    {
        try {
            $deleted = DB::table('products')->where('id', $id)->delete();

            if ($deleted === 0) {
                return response()->json(['success' => false, 'message' => 'Produit non trouvé'], 404);
            }

            return response()->json(['success' => true, 'message' => 'Produit supprimé avec succès']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erreur lors de la suppression', 'error' => $e->getMessage()], 500);
        }
    }
}