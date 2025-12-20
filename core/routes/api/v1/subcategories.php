<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

// ============================================
// ROUTES SOUS-CATÉGORIES
// ============================================

// Liste toutes les sous-catégories
Route::get('/subcategories', function () {
    try {
        $subcategories = DB::table('subcategories')
            ->join('categories', 'subcategories.category_id', '=', 'categories.id')
            ->select(
                'subcategories.*',
                'categories.name as category_name'
            )
            ->orderBy('categories.name')
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

// Récupérer une sous-catégorie par ID
Route::get('/subcategories/{id}', function ($id) {
    try {
        $subcategory = DB::table('subcategories')
            ->join('categories', 'subcategories.category_id', '=', 'categories.id')
            ->where('subcategories.id', $id)
            ->select(
                'subcategories.*',
                'categories.name as category_name'
            )
            ->first();
        
        if (!$subcategory) {
            return response()->json([
                'success' => false,
                'message' => 'Sous-catégorie non trouvée'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $subcategory
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors du chargement de la sous-catégorie',
            'error' => $e->getMessage()
        ], 500);
    }
});

// Récupérer les sous-catégories d'une catégorie
Route::get('/categories/{categoryId}/subcategories', function ($categoryId) {
    try {
        $subcategories = DB::table('subcategories')
            ->where('category_id', $categoryId)
            ->orderBy('name')
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

// Créer une sous-catégorie
Route::post('/subcategories', function (Request $request) {
    try {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:subcategories,code',
            'description' => 'nullable|string'
        ]);
        
        // Générer un code automatique si non fourni
        if (!isset($validated['code']) || empty($validated['code'])) {
            $validated['code'] = strtoupper(substr($validated['name'], 0, 3)) . '-' . rand(100, 999);
        }
        
        $id = DB::table('subcategories')->insertGetId([
            'category_id' => $validated['category_id'],
            'name' => $validated['name'],
            'code' => $validated['code'],
            'description' => $validated['description'] ?? null,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        $subcategory = DB::table('subcategories')->where('id', $id)->first();
        
        return response()->json([
            'success' => true,
            'message' => 'Sous-catégorie créée avec succès',
            'data' => $subcategory
        ], 201);
    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur de validation',
            'errors' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la création de la sous-catégorie',
            'error' => $e->getMessage()
        ], 500);
    }
});

// Modifier une sous-catégorie
Route::put('/subcategories/{id}', function (Request $request, $id) {
    try {
        $validated = $request->validate([
            'category_id' => 'sometimes|required|exists:categories,id',
            'name' => 'sometimes|required|string|max:255',
            'code' => 'sometimes|nullable|string|max:50|unique:subcategories,code,' . $id,
            'description' => 'nullable|string'
        ]);
        
        $subcategory = DB::table('subcategories')->where('id', $id)->first();
        
        if (!$subcategory) {
            return response()->json([
                'success' => false,
                'message' => 'Sous-catégorie non trouvée'
            ], 404);
        }
        
        $validated['updated_at'] = now();
        
        DB::table('subcategories')->where('id', $id)->update($validated);
        
        $updatedSubcategory = DB::table('subcategories')->where('id', $id)->first();
        
        return response()->json([
            'success' => true,
            'message' => 'Sous-catégorie mise à jour avec succès',
            'data' => $updatedSubcategory
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
            'message' => 'Erreur lors de la mise à jour de la sous-catégorie',
            'error' => $e->getMessage()
        ], 500);
    }
});

// Supprimer une sous-catégorie
Route::delete('/subcategories/{id}', function ($id) {
    try {
        $subcategory = DB::table('subcategories')->where('id', $id)->first();
        
        if (!$subcategory) {
            return response()->json([
                'success' => false,
                'message' => 'Sous-catégorie non trouvée'
            ], 404);
        }
        
        // Vérifier s'il y a des produits liés
        $productCount = DB::table('products')->where('subcategory_id', $id)->count();
        
        if ($productCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "Impossible de supprimer la sous-catégorie : {$productCount} produit(s) lié(s)"
            ], 400);
        }
        
        DB::table('subcategories')->where('id', $id)->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Sous-catégorie supprimée avec succès'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la suppression de la sous-catégorie',
            'error' => $e->getMessage()
        ], 500);
    }
});
