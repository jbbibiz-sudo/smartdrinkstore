<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Models\DepositReturn;
use App\Models\DepositType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DepositController extends Controller
{
    // ====================================
    // TYPES D'EMBALLAGES
    // ====================================

    /**
     * Liste tous les types d'emballages
     */
    public function depositTypes()
    {
        try {
            $types = DepositType::orderBy('name')->get();
            
            return response()->json([
                'success' => true,
                'data' => $types
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des types: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crée un nouveau type d'emballage
     */
    public function storeDepositType(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:deposit_types,code',
            'category' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'initial_stock' => 'required|integer|min:0',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation échouée',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $type = DepositType::create([
                'name' => $request->name,
                'code' => $request->code,
                'category' => $request->category,
                'amount' => $request->amount,
                'initial_stock' => $request->initial_stock,
                'current_stock' => $request->initial_stock,
                'description' => $request->description,
                'is_active' => true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Type d\'emballage créé avec succès',
                'data' => $type
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Affiche un type d'emballage
     */
    public function showDepositType($id)
    {
        try {
            $type = DepositType::findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $type
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Type non trouvé'
            ], 404);
        }
    }

    /**
     * Met à jour un type d'emballage
     */
    public function updateDepositType(Request $request, $id)
    {
        $type = DepositType::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'code' => 'sometimes|required|string|unique:deposit_types,code,' . $id,
            'category' => 'sometimes|required|string',
            'amount' => 'sometimes|required|numeric|min:0',
            'current_stock' => 'sometimes|required|integer|min:0',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation échouée',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $type->update($request->only([
                'name', 'code', 'category', 'amount', 
                'current_stock', 'description', 'is_active'
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Type mis à jour avec succès',
                'data' => $type
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprime un type d'emballage
     */
    public function destroyDepositType($id)
    {
        try {
            $type = DepositType::findOrFail($id);

            // Vérifier s'il y a des consignes actives
            $activeDeposits = Deposit::where('deposit_type_id', $id)
                ->where('status', '!=', 'returned')
                ->count();

            if ($activeDeposits > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de supprimer: il existe des consignes actives avec ce type'
                ], 422);
            }

            $type->delete();

            return response()->json([
                'success' => true,
                'message' => 'Type supprimé avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression: ' . $e->getMessage()
            ], 500);
        }
    }

    // ====================================
    // CONSIGNES (TRANSACTIONS)
    // ====================================

    /**
     * Liste toutes les consignes avec filtres
     */
    public function index(Request $request)
    {
        try {
            $query = Deposit::with(['depositType', 'customer', 'supplier']);

            // Filtres
            if ($request->has('direction')) {
                $query->where('direction', $request->direction);
            }

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('partner_type')) {
                $query->where('partner_type', $request->partner_type);
            }

            $deposits = $query->orderBy('created_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => $deposits
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Affiche une consigne
     */
    public function show($id)
    {
        try {
            $deposit = Deposit::with(['depositType', 'customer', 'supplier', 'returns'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $deposit
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Consigne non trouvée'
            ], 404);
        }
    }

    /**
     * Crée une consigne sortante (vers client)
     */
    public function storeOutgoing(Request $request)
    {
        return $this->createDeposit($request, 'outgoing', 'customer');
    }

    /**
     * Crée une consigne entrante (du fournisseur)
     */
    public function storeIncoming(Request $request)
    {
        return $this->createDeposit($request, 'incoming', 'supplier');
    }

    /**
     * Méthode privée pour créer une consigne
     */
    private function createDeposit(Request $request, $direction, $partnerType)
    {
        $validator = Validator::make($request->all(), [
            'deposit_type_id' => 'required|exists:deposit_types,id',
            'partner_id' => 'required|integer',
            'quantity' => 'required|integer|min:1',
            'expected_return_at' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation échouée',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $depositType = DepositType::findOrFail($request->deposit_type_id);

            // Vérifier le stock pour consignes sortantes
            if ($direction === 'outgoing' && $depositType->current_stock < $request->quantity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock d\'emballages insuffisant'
                ], 422);
            }

            // Créer la consigne
            $deposit = Deposit::create([
                'deposit_type_id' => $request->deposit_type_id,
                'direction' => $direction,
                'partner_type' => $partnerType,
                'partner_id' => $request->partner_id,
                'quantity' => $request->quantity,
                'unit_amount' => $depositType->amount,
                'total_amount' => $request->quantity * $depositType->amount,
                'quantity_pending' => $request->quantity,
                'expected_return_at' => $request->expected_return_at,
                'notes' => $request->notes,
                'status' => 'active',
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Consigne créée avec succès',
                'data' => $deposit->load(['depositType', 'customer', 'supplier'])
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Traite un retour d'emballages
     */
    public function processReturn(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:1',
            'good_condition' => 'required|integer|min:0',
            'damaged' => 'required|integer|min:0',
            'lost' => 'required|integer|min:0',
            'damage_penalty' => 'nullable|numeric|min:0',
            'late_penalty' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation échouée',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $deposit = Deposit::findOrFail($id);

            // Vérifier que la quantité ne dépasse pas la quantité en attente
            if ($request->quantity > $deposit->quantity_pending) {
                return response()->json([
                    'success' => false,
                    'message' => 'La quantité retournée dépasse la quantité en attente'
                ], 422);
            }

            // Créer le retour
            $return = DepositReturn::create([
                'deposit_id' => $deposit->id,
                'quantity' => $request->quantity,
                'good_condition' => $request->good_condition,
                'damaged' => $request->damaged,
                'lost' => $request->lost,
                'damage_penalty' => $request->damage_penalty ?? 0,
                'late_penalty' => $request->late_penalty ?? 0,
                'total_penalty' => ($request->damage_penalty ?? 0) + ($request->late_penalty ?? 0),
                'refund_amount' => ($request->good_condition * $deposit->unit_amount) 
                    - (($request->damage_penalty ?? 0) + ($request->late_penalty ?? 0)),
                'notes' => $request->notes,
                'returned_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Retour traité avec succès',
                'data' => $return->load('deposit')
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du traitement: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Historique des retours d'une consigne
     */
    public function returnHistory($id)
    {
        try {
            $returns = DepositReturn::where('deposit_id', $id)
                ->orderBy('returned_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $returns
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Liste tous les retours d'emballages
     */
    public function returns()
    {
        try {
            $returns = DepositReturn::with(['deposit.depositType', 'deposit.customer', 'deposit.supplier'])
                ->orderBy('returned_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $returns
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Affiche un retour
     */
    public function showReturn($id)
    {
        try {
            $return = DepositReturn::with(['deposit.depositType', 'deposit.customer', 'deposit.supplier'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $return
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Retour non trouvé'
            ], 404);
        }
    }

    /**
     * Supprime une consigne (si aucun retour)
     */
    public function destroy($id)
    {
        try {
            $deposit = Deposit::findOrFail($id);

            // Vérifier s'il y a des retours
            if ($deposit->returns()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de supprimer: des retours ont déjà été enregistrés'
                ], 422);
            }

            // Restaurer le stock si nécessaire
            if ($deposit->direction === 'outgoing') {
                $deposit->depositType->increment('current_stock', $deposit->quantity);
            } else {
                $deposit->depositType->decrement('current_stock', $deposit->quantity);
            }

            $deposit->delete();

            return response()->json([
                'success' => true,
                'message' => 'Consigne supprimée avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Statistiques des consignes
     */
    public function statistics()
    {
        try {
            $stats = [
                'active_deposits' => Deposit::where('status', 'active')->count(),
                'total_units_out' => Deposit::where('direction', 'outgoing')
                    ->where('status', '!=', 'returned')
                    ->sum('quantity_pending'),
                'total_deposits_amount' => Deposit::where('status', '!=', 'returned')
                    ->sum('total_amount'),
                'total_penalties' => DepositReturn::sum('total_penalty'),
                'total_refunds' => DepositReturn::sum('refund_amount'),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du calcul: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Consignes en attente
     */
    public function pending()
    {
        try {
            $deposits = Deposit::with(['depositType', 'customer', 'supplier'])
                ->whereIn('status', ['active', 'partial'])
                ->orderBy('expected_return_at')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $deposits
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement: ' . $e->getMessage()
            ], 500);
        }
    }
}