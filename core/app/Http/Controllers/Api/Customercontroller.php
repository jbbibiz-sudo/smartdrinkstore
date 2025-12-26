<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    /**
     * Liste tous les clients
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            $customers = Customer::orderBy('name', 'asc')->get();
            
            return response()->json([
                'success' => true,
                'data' => $customers,
                'message' => 'Clients récupérés avec succès'
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des clients',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Affiche un client spécifique
     * 
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $customer = Customer::findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $customer,
                'message' => 'Client récupéré avec succès'
            ], 200);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Client introuvable'
            ], 404);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du client',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crée un nouveau client
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
            'email' => 'nullable|email|max:255|unique:customers,email',
            'address' => 'nullable|string|max:500',
        ], [
            'name.required' => 'Le nom du client est obligatoire',
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
            
            $customer = Customer::create([
                'name' => $request->name,
                'phone' => $request->phone,
                'email' => $request->email,
                'address' => $request->address,
                'balance' => 0, // Solde initial à 0
            ]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'data' => $customer,
                'message' => 'Client créé avec succès'
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du client',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Met à jour un client existant
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            $customer = Customer::findOrFail($id);
            
            // Validation des données
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'phone' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:255|unique:customers,email,' . $id,
                'address' => 'nullable|string|max:500',
            ], [
                'name.required' => 'Le nom du client est obligatoire',
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
            
            $customer->update([
                'name' => $request->name,
                'phone' => $request->phone,
                'email' => $request->email,
                'address' => $request->address,
            ]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'data' => $customer,
                'message' => 'Client mis à jour avec succès'
            ], 200);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Client introuvable'
            ], 404);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du client',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprime un client
     * 
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $customer = Customer::findOrFail($id);
            
            // Vérifier si le client a un solde en cours
            if ($customer->balance > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de supprimer ce client. Il a encore un solde impayé de ' . number_format($customer->balance, 0, ',', ' ') . ' FCFA'
                ], 400);
            }
            
            DB::beginTransaction();
            
            // Soft delete si vous utilisez SoftDeletes, sinon delete()
            $customer->delete();
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Client supprimé avec succès'
            ], 200);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Client introuvable'
            ], 404);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du client',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Recherche des clients
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        try {
            $query = $request->input('query', '');
            
            $customers = Customer::where('name', 'LIKE', "%{$query}%")
                ->orWhere('phone', 'LIKE', "%{$query}%")
                ->orWhere('email', 'LIKE', "%{$query}%")
                ->orderBy('name', 'asc')
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $customers,
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
     * Récupère les statistiques des clients
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function stats()
    {
        try {
            $stats = [
                'total_customers' => Customer::count(),
                'customers_with_balance' => Customer::where('balance', '>', 0)->count(),
                'total_balance' => Customer::sum('balance'),
                'average_balance' => Customer::where('balance', '>', 0)->avg('balance') ?? 0,
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

    /**
     * Ajuste le solde d'un client (paiement partiel)
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function adjustBalance(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0',
            'type' => 'required|in:payment,adjustment',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $customer = Customer::findOrFail($id);
            
            DB::beginTransaction();
            
            if ($request->type === 'payment') {
                // Paiement - réduction du solde
                $customer->balance -= $request->amount;
                
                // S'assurer que le solde ne devient pas négatif
                if ($customer->balance < 0) {
                    $customer->balance = 0;
                }
            } else {
                // Ajustement manuel
                $customer->balance = $request->amount;
            }
            
            $customer->save();
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'data' => $customer,
                'message' => 'Solde ajusté avec succès'
            ], 200);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Client introuvable'
            ], 404);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'ajustement du solde',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
