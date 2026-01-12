<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class SupplierController extends Controller
{
    /**
     * Liste tous les fournisseurs
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            $suppliers = Supplier::orderBy('name', 'asc')->get();
            
            return response()->json([
                'success' => true,
                'data' => $suppliers,
                'message' => 'Fournisseurs récupérés avec succès'
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
     * Affiche un fournisseur spécifique
     * 
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $supplier = Supplier::findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $supplier,
                'message' => 'Fournisseur récupéré avec succès'
            ], 200);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Fournisseur introuvable'
            ], 404);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du fournisseur',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crée un nouveau fournisseur
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Validation des données
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255|unique:suppliers,email',
            'address' => 'nullable|string|max:500',
        ], [
            'name.required' => 'Le nom du fournisseur est obligatoire',
            'email.email' => 'L\'adresse email n\'est pas valide',
            'email.unique' => 'Cette adresse email est déjà utilisée',
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
            
            $supplier = Supplier::create([
                'name' => $request->name,
                'phone' => $request->phone,
                'email' => $request->email,
                'address' => $request->address,
            ]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'data' => $supplier,
                'message' => 'Fournisseur créé avec succès'
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du fournisseur',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Met à jour un fournisseur existant
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            $supplier = Supplier::findOrFail($id);
            
            // Validation des données
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'phone' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:255|unique:suppliers,email,' . $id,
                'address' => 'nullable|string|max:500',
            ], [
                'name.required' => 'Le nom du fournisseur est obligatoire',
                'email.email' => 'L\'adresse email n\'est pas valide',
                'email.unique' => 'Cette adresse email est déjà utilisée',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur de validation',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();
            
            $supplier->update([
                'name' => $request->name,
                'phone' => $request->phone,
                'email' => $request->email,
                'address' => $request->address,
            ]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'data' => $supplier,
                'message' => 'Fournisseur mis à jour avec succès'
            ], 200);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Fournisseur introuvable'
            ], 404);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du fournisseur',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprime un fournisseur
     * 
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $supplier = Supplier::findOrFail($id);
            
            // Vérifier si le fournisseur a des produits associés
            $hasProducts = DB::table('products')
                ->where('supplier_id', $id)
                ->exists();
            
            if ($hasProducts) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de supprimer ce fournisseur. Des produits lui sont associés.'
                ], 400);
            }
            
            DB::beginTransaction();
            
            // Soft delete si vous utilisez SoftDeletes, sinon delete()
            $supplier->delete();
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Fournisseur supprimé avec succès'
            ], 200);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Fournisseur introuvable'
            ], 404);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du fournisseur',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Recherche des fournisseurs
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        try {
            $query = $request->input('query', '');
            
            $suppliers = Supplier::where('name', 'LIKE', "%{$query}%")
                ->orWhere('phone', 'LIKE', "%{$query}%")
                ->orWhere('email', 'LIKE', "%{$query}%")
                ->orderBy('name', 'asc')
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $suppliers,
                'message' => 'Recherche effectuée avec succès'
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la recherche',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupère les produits d'un fournisseur
     */
    public function products($id)
    {
        try {
            $supplier = Supplier::findOrFail($id);
            $products = $supplier->products()->get();
            
            return response()->json([
                'success' => true,
                'data' => $products,
                'message' => 'Produits récupérés avec succès'
            ], 200);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Fournisseur introuvable'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des produits',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupère les achats récents d'un fournisseur
     */
    public function purchases($id, Request $request)
    {
        try {
            $supplier = Supplier::findOrFail($id);
            $limit = $request->input('limit', 5);
            
            $purchases = $supplier->purchases()
                ->orderBy('date', 'desc')
                ->limit($limit)
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $purchases,
                'message' => 'Achats récupérés avec succès'
            ], 200);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Fournisseur introuvable'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des achats',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupère les statistiques des fournisseurs
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function stats()
    {
        try {
            $stats = [
                'total_suppliers' => Supplier::count(),
                'suppliers_with_contact' => Supplier::where(function($query) {
                    $query->whereNotNull('phone')
                          ->orWhereNotNull('email');
                })->count(),
                'suppliers_with_products' => DB::table('products')
                    ->distinct('supplier_id')
                    ->whereNotNull('supplier_id')
                    ->count('supplier_id'),
            ];
            
            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Statistiques récupérées avec succès'
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
