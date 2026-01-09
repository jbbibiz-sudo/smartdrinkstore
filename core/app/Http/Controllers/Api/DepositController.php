<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Deposit;
use Illuminate\Http\Request;

class DepositController extends Controller
{
    /**
     * Liste toutes les consignes
     */
    public function index(Request $request)
    {
        $query = Deposit::with(['depositType', 'customer', 'sale']);

        // Filtres optionnels
        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $deposits = $query->latest()->get();

        return response()->json([
            'success' => true,
            'data' => $deposits
        ]);
    }

    /**
     * Créer une nouvelle consigne
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'sale_id' => 'required|exists:sales,id',
            'customer_id' => 'required|exists:customers,id',
            'deposit_type_id' => 'required|exists:deposit_types,id',
            'quantity' => 'required|integer|min:1',
            'amount' => 'required|numeric|min:0',
            'status' => 'required|in:pending,returned',
        ]);

        $deposit = Deposit::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Consigne enregistrée avec succès',
            'data' => $deposit->load(['depositType', 'customer', 'sale'])
        ], 201);
    }

    /**
     * Statistiques des consignes
     */
    public function summary()
    {
        try {
            $activeDeposits = Deposit::where('status', 'pending')->count();
            $totalUnitsOut = Deposit::where('status', 'pending')->sum('quantity');
            $totalDepositsAmount = Deposit::where('status', 'pending')->sum('amount');
            
            // Calculer les pénalités si vous avez ce système
            $totalPenalties = 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'active_deposits' => $activeDeposits,
                    'total_units_out' => $totalUnitsOut,
                    'total_deposits_amount' => $totalDepositsAmount,
                    'total_penalties' => $totalPenalties
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du calcul des statistiques',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Afficher une consigne spécifique
     */
    public function show(Deposit $deposit)
    {
        $deposit->load(['depositType', 'customer', 'sale']);

        return response()->json([
            'success' => true,
            'data' => $deposit
        ]);
    }

    /**
     * Mettre à jour une consigne
     */
    public function update(Request $request, Deposit $deposit)
    {
        $validated = $request->validate([
            'quantity' => 'sometimes|integer|min:1',
            'amount' => 'sometimes|numeric|min:0',
            'status' => 'sometimes|in:pending,returned',
        ]);

        $deposit->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Consigne mise à jour',
            'data' => $deposit->load(['depositType', 'customer', 'sale'])
        ]);
    }

    /**
     * Supprimer une consigne
     */
    public function destroy(Deposit $deposit)
    {
        $deposit->delete();

        return response()->json([
            'success' => true,
            'message' => 'Consigne supprimée'
        ]);
    }
}