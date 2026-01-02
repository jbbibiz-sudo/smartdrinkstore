<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DepositType;
use Illuminate\Support\Facades\Log;

class DepositTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     * GET /api/v1/deposit-types
     */
    public function index()
    {
        try {
            $depositTypes = DepositType::orderBy('name')->get();
            
            return response()->json([
                'success' => true,
                'data' => $depositTypes
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Erreur liste types: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des types',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     * POST /api/v1/deposit-types
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|max:50|unique:deposit_types,code',
                'name' => 'required|string|max:255',
                'description' => 'nullable|string|max:1000',
                'category' => 'nullable|string|max:100',
                'amount' => 'required|numeric|min:0',
                'initial_stock' => 'nullable|integer|min:0',
                'current_stock' => 'nullable|integer|min:0',
                'is_active' => 'nullable|boolean',
            ]);

            // Set defaults
            $validated['initial_stock'] = $validated['initial_stock'] ?? 0;
            $validated['current_stock'] = $validated['current_stock'] ?? $validated['initial_stock'];
            $validated['is_active'] = $validated['is_active'] ?? true;

            $depositType = DepositType::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Type d\'emballage créé avec succès',
                'data' => $depositType
            ], 201);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            Log::error('Erreur création type: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     * GET /api/v1/deposit-types/{id}
     */
    public function show(string $id)
    {
        try {
            $depositType = DepositType::findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $depositType
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Type non trouvé'
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     * PUT /api/v1/deposit-types/{id}
     */
    public function update(Request $request, string $id)
    {
        try {
            $depositType = DepositType::findOrFail($id);

            $validated = $request->validate([
                'code' => 'required|string|max:50|unique:deposit_types,code,' . $id,
                'name' => 'required|string|max:255',
                'description' => 'nullable|string|max:1000',
                'category' => 'nullable|string|max:100',
                'amount' => 'required|numeric|min:0',
                'initial_stock' => 'nullable|integer|min:0',
                'current_stock' => 'nullable|integer|min:0',
                'is_active' => 'nullable|boolean',
            ]);

            $depositType->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Type mis à jour avec succès',
                'data' => $depositType
            ], 200);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            Log::error('Erreur mise à jour type: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     * DELETE /api/v1/deposit-types/{id}
     */
    public function destroy(string $id)
    {
        try {
            $depositType = DepositType::findOrFail($id);
            $depositType->delete();

            return response()->json([
                'success' => true,
                'message' => 'Type supprimé avec succès'
            ], 200);
            
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer ce type car il est utilisé'
            ], 409);
            
        } catch (\Exception $e) {
            Log::error('Erreur suppression type: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression'
            ], 500);
        }
    }
}