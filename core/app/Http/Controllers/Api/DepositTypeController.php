<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DepositType;
use Illuminate\Http\Request;

class DepositTypeController extends Controller
{
    /**
     * Liste tous les types de consignes
     */
    public function index()
    {
        $depositTypes = DepositType::all();
        
        return response()->json([
            'success' => true,
            'data' => $depositTypes
        ]);
    }

    /**
     * Créer un nouveau type de consigne
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'deposit_amount' => 'required|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        $depositType = DepositType::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Type de consigne créé avec succès',
            'data' => $depositType
        ], 201);
    }

    /**
     * Afficher un type de consigne spécifique
     */
    public function show(DepositType $depositType)
    {
        return response()->json([
            'success' => true,
            'data' => $depositType
        ]);
    }

    /**
     * Mettre à jour un type de consigne
     */
    public function update(Request $request, DepositType $depositType)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'deposit_amount' => 'sometimes|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        $depositType->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Type de consigne mis à jour',
            'data' => $depositType
        ]);
    }

    /**
     * Supprimer un type de consigne
     */
    public function destroy(DepositType $depositType)
    {
        $depositType->delete();

        return response()->json([
            'success' => true,
            'message' => 'Type de consigne supprimé'
        ]);
    }
}