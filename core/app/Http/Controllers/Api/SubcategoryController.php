<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subcategory;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class SubcategoryController extends Controller
{
    /**
     * Liste toutes les sous-catégories
     */
    public function index(Request $request)
    {
        try {
            $query = Subcategory::with('category');

            // Filtres optionnels
            if ($request->has('category_id')) {
                $query->where('category_id', $request->category_id);
            }
            
            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }
            
            if ($request->get('with_products_count', false)) {
                $query->withCount('products');
            }

            // Tri
            $sortBy = $request->get('sort_by', 'position');
            $sortOrder = $request->get('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);

            $subcategories = $query->get();

            return response()->json([
                'success' => true,
                'data' => $subcategories
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur chargement sous-catégories:', ['message' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des sous-catégories',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crée une nouvelle sous-catégorie
     * ✅ CORRECTION: Tous les champs sont optionnels sauf name et category_id
     */
    public function store(Request $request)
    {
        try {
            // ✅ Validation simplifiée
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'category_id' => 'required|exists:categories,id',
                'description' => 'nullable|string',
                'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
                'position' => 'nullable|integer|min:0',
                'is_active' => 'nullable|boolean'
            ]);

            // ✅ Le modèle génère automatiquement code et slug via boot()
            $subcategory = Subcategory::create($validated);

            Log::info('Sous-catégorie créée:', [
                'id' => $subcategory->id,
                'name' => $subcategory->name,
                'category_id' => $subcategory->category_id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Sous-catégorie créée avec succès',
                'data' => $subcategory->load('category')
            ], 201);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            Log::error('Erreur création sous-catégorie:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la sous-catégorie',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Affiche une sous-catégorie spécifique
     */
    public function show($id)
    {
        try {
            $subcategory = Subcategory::with([
                'category',
                'products' => function($query) {
                    $query->where('is_active', true)->limit(10);
                }
            ])->findOrFail($id);
            
            // Statistiques
            $stats = [
                'total_products' => $subcategory->products()->count(),
                'active_products' => $subcategory->products()->where('is_active', true)->count(),
                'low_stock_products' => $subcategory->products()
                    ->where('is_active', true)
                    ->whereRaw('stock <= min_stock')
                    ->count(),
                'total_stock_value' => $subcategory->products()
                    ->where('is_active', true)
                    ->get()
                    ->sum(function($p) {
                        return $p->stock * ($p->cost_price ?? 0);
                    }),
            ];
            
            return response()->json([
                'success' => true,
                'data' => [
                    'subcategory' => $subcategory,
                    'stats' => $stats
                ]
            ]);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sous-catégorie introuvable'
            ], 404);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Met à jour une sous-catégorie
     */
    public function update(Request $request, $id)
    {
        try {
            $subcategory = Subcategory::findOrFail($id);
            
            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'category_id' => 'sometimes|required|exists:categories,id',
                'description' => 'nullable|string',
                'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
                'position' => 'nullable|integer|min:0',
                'is_active' => 'nullable|boolean'
            ]);

            $subcategory->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Sous-catégorie mise à jour avec succès',
                'data' => $subcategory->fresh('category')
            ]);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sous-catégorie introuvable'
            ], 404);
            
        } catch (\Exception $e) {
            Log::error('Erreur mise à jour sous-catégorie:', ['message' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprime une sous-catégorie
     */
    public function destroy($id)
    {
        try {
            $subcategory = Subcategory::findOrFail($id);
            
            $count = $subcategory->products()->count();
            
            if ($count > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de supprimer, des produits sont liés à cette sous-catégorie',
                    'products_count' => $count
                ], 422);
            }
            
            $subcategory->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Sous-catégorie supprimée avec succès'
            ]);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sous-catégorie introuvable'
            ], 404);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Liste les produits d'une sous-catégorie
     */
    public function products($id, Request $request)
    {
        try {
            $subcategory = Subcategory::findOrFail($id);
            
            $query = $subcategory->products()->with('category');
            
            if ($request->get('active_only', true)) {
                $query->where('is_active', true);
            }
            
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('sku', 'like', "%{$search}%");
                });
            }
            
            $products = $query->get();
            
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
    }
}