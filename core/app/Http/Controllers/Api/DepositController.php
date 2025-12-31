<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Deposit;
use App\Models\DepositReturn;
use App\Models\DepositType;
use Illuminate\Support\Facades\Log;

class DepositController extends Controller
{
    /**
     * Liste toutes les consignes
     * GET /api/v1/deposits
     */
    public function index()
    {
        try {
            $deposits = Deposit::with(['depositType', 'customer', 'supplier'])
                ->orderBy('created_at', 'desc')
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $deposits
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Erreur liste consignes: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des consignes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Affiche une consigne
     * GET /api/v1/deposits/{id}
     */
    public function show($id)
    {
        try {
            $deposit = Deposit::with(['depositType', 'customer', 'supplier', 'returns'])
                ->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $deposit
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Erreur affichage consigne: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Consigne non trouvée'
            ], 404);
        }
    }

    /**
     * Statistiques des consignes
     * GET /api/v1/deposits/stats/summary
     */
    public function statistics()
    {
        try {
            $activeDeposits = Deposit::whereIn('status', ['active', 'partially_returned'])->get();
            
            $stats = [
                'active_deposits' => $activeDeposits->count(),
                'total_units_out' => $activeDeposits->sum('quantity_pending') ?? 0,
                'total_deposits_amount' => $activeDeposits->sum('total_deposit_amount') ?? 0,
                'total_penalties' => DepositReturn::sum('total_penalty') ?? 0,
            ];
            
            return response()->json([
                'success' => true,
                'data' => $stats
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Erreur stats consignes: ' . $e->getMessage());
            
            return response()->json([
                'success' => true,
                'data' => [
                    'active_deposits' => 0,
                    'total_units_out' => 0,
                    'total_deposits_amount' => 0,
                    'total_penalties' => 0,
                ]
            ], 200);
        }
    }

    /**
     * Liste tous les retours
     * GET /api/v1/deposit-returns
     */
    public function returns()
    {
        try {
            $returns = DepositReturn::with(['deposit.depositType', 'deposit.customer'])
                ->orderBy('created_at', 'desc')
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $returns
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Erreur liste retours: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des retours'
            ], 500);
        }
    }

    /**
     * Créer une consigne sortante (vers client)
     * POST /api/v1/deposits/outgoing
     */
    public function storeOutgoing(Request $request)
    {
        try {
            // TODO: Implémenter la logique métier
            return response()->json([
                'success' => true,
                'message' => 'Consigne créée (à implémenter)',
                'data' => []
            ], 201);
            
        } catch (\Exception $e) {
            Log::error('Erreur création consigne sortante: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création'
            ], 500);
        }
    }

    /**
     * Créer une consigne entrante (du fournisseur)
     * POST /api/v1/deposits/incoming
     */
    public function storeIncoming(Request $request)
    {
        try {
            // TODO: Implémenter la logique métier
            return response()->json([
                'success' => true,
                'message' => 'Consigne créée (à implémenter)',
                'data' => []
            ], 201);
            
        } catch (\Exception $e) {
            Log::error('Erreur création consigne entrante: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création'
            ], 500);
        }
    }

    /**
     * Traiter un retour d'emballages
     * POST /api/v1/deposits/{id}/return
     */
    public function processReturn(Request $request, $id)
    {
        try {
            // TODO: Implémenter la logique métier
            return response()->json([
                'success' => true,
                'message' => 'Retour traité (à implémenter)',
                'data' => []
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Erreur traitement retour: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du traitement'
            ], 500);
        }
    }

    /**
     * Supprimer une consigne
     * DELETE /api/v1/deposits/{id}
     */
    public function destroy($id)
    {
        try {
            $deposit = Deposit::findOrFail($id);
            $deposit->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Consigne supprimée'
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Erreur suppression consigne: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression'
            ], 500);
        }
    }

    /**
     * Consignes en attente
     * GET /api/v1/deposits/pending/list
     */
    public function pending()
    {
        try {
            $pending = Deposit::where('quantity_pending', '>', 0)
                ->with(['depositType', 'customer', 'supplier'])
                ->orderBy('created_at', 'desc')
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $pending
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Erreur consignes en attente: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement'
            ], 500);
        }
    }

    /**
     * Afficher un retour
     * GET /api/v1/deposit-returns/{id}
     */
    public function showReturn($id)
    {
        try {
            $return = DepositReturn::with(['deposit.depositType', 'deposit.customer'])
                ->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $return
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Retour non trouvé'
            ], 404);
        }
    }

    /**
     * Historique des retours d'une consigne
     * GET /api/v1/deposits/{id}/returns
     */
    public function returnHistory($id)
    {
        try {
            $returns = DepositReturn::where('deposit_id', $id)
                ->orderBy('created_at', 'desc')
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $returns
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement'
            ], 500);
        }
    }
}