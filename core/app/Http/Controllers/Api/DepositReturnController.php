<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Models\DepositReturn;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DepositReturnController extends Controller
{
    /**
     * Liste tous les retours de consignes
     */
    public function index(Request $request)
    {
        $query = DepositReturn::with(['deposit.depositType', 'deposit.customer']);

        if ($request->has('customer_id')) {
            $query->whereHas('deposit', function($q) use ($request) {
                $q->where('customer_id', $request->customer_id);
            });
        }

        $returns = $query->latest()->get();

        return response()->json([
            'success' => true,
            'data' => $returns
        ]);
    }

    /**
     * Enregistrer un retour de consigne
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'deposit_id' => 'required|exists:deposits,id',
            'quantity_returned' => 'required|integer|min:1',
            'refund_amount' => 'required|numeric|min:0',
            'return_date' => 'required|date',
        ]);

        DB::beginTransaction();
        try {
            $deposit = Deposit::findOrFail($validated['deposit_id']);

            // Vérifier que la quantité retournée n'excède pas la quantité initiale
            if ($validated['quantity_returned'] > $deposit->quantity) {
                return response()->json([
                    'success' => false,
                    'message' => 'La quantité retournée ne peut pas excéder la quantité initiale'
                ], 400);
            }

            // Créer le retour
            $return = DepositReturn::create($validated);

            // Mettre à jour le statut du dépôt si tout est retourné
            if ($validated['quantity_returned'] >= $deposit->quantity) {
                $deposit->update(['status' => 'returned']);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Retour de consigne enregistré avec succès',
                'data' => $return->load('deposit.depositType')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'enregistrement du retour',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Afficher un retour spécifique
     */
    public function show(DepositReturn $depositReturn)
    {
        $depositReturn->load(['deposit.depositType', 'deposit.customer']);

        return response()->json([
            'success' => true,
            'data' => $depositReturn
        ]);
    }
}