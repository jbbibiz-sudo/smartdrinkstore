<?php
/**
 * =============================================================================
 * CONTRÔLEUR DES CATÉGORIES - VERSION CORRIGÉE
 * =============================================================================
 * Fichier: app/Http/Controllers/Api/CategoryController.php
 * 
 * ✅ Validation simplifiée (color et icon optionnels)
 * ✅ Utilise le modèle Category (boot() est appelé)
 * ✅ Format de réponse standardisé
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class CategoryController extends Controller
{
    /**
     * Liste toutes les catégories
     */
    public function index()
    {
        try {
            $categories = Category::with('subcategories')
                ->orderBy('name')
                ->get();
                
            return response()->json([
                'success' => true,
                'data' => $categories
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur chargement catégories:', ['message' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des catégories',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crée une nouvelle catégorie
     * ✅ CORRECTION: color et icon sont maintenant OPTIONNELS
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:categories',
            'description' => 'nullable|string',
            'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'icon' => 'nullable|string|max:50'
        ], [
            'name.required' => 'Le nom de la catégorie est obligatoire',
            'name.unique' => 'Cette catégorie existe déjà',
            'color.regex' => 'Le format de couleur est invalide (ex: #FF5733)'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // ✅ Utilise le modèle Eloquent (boot() sera appelé)
            $category = Category::create($request->only([
                'name',
                'description',
                'color',
                'icon'
            ]));

            Log::info('Catégorie créée:', [
                'id' => $category->id,
                'name' => $category->name,
                'code' => $category->code,
                'slug' => $category->slug
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Catégorie créée avec succès',
                'data' => $category
            ], 201);
            
        } catch (\Exception $e) {
            Log::error('Erreur création catégorie:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la catégorie',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Affiche une catégorie spécifique
     */
    public function show($id)
    {
        try {
            $category = Category::with(['subcategories', 'products'])->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $category
            ]);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Catégorie introuvable'
            ], 404);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement de la catégorie',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Met à jour une catégorie
     */
    public function update(Request $request, $id)
    {
        try {
            $category = Category::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255|unique:categories,name,' . $id,
                'description' => 'nullable|string',
                'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
                'icon' => 'nullable|string|max:50'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur de validation',
                    'errors' => $validator->errors()
                ], 422);
            }

            // ✅ Utilise update() du modèle (boot() sera appelé si le nom change)
            $category->update($request->only([
                'name',
                'description',
                'color',
                'icon'
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Catégorie mise à jour avec succès',
                'data' => $category->fresh()
            ]);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Catégorie introuvable'
            ], 404);
            
        } catch (\Exception $e) {
            Log::error('Erreur mise à jour catégorie:', ['message' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprime une catégorie
     */
    public function destroy($id)
    {
        try {
            $category = Category::findOrFail($id);
            
            // Vérifier si des produits sont liés
            if ($category->products()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de supprimer une catégorie contenant des produits'
                ], 422);
            }

            $category->delete();

            return response()->json([
                'success' => true,
                'message' => 'Catégorie supprimée avec succès'
            ]);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Catégorie introuvable'
            ], 404);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}