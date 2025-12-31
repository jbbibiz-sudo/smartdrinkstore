<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DepositType;

class DepositTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $depositTypes = DepositType::all();
        return response()->json($depositTypes);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:deposit_types,code',
            'name' => 'required|string|max:255|unique:deposit_types,name',
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

        return response()->json($depositType, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $depositType = DepositType::findOrFail($id);
        return response()->json($depositType);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $depositType = DepositType::findOrFail($id);

        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:deposit_types,code,' . $id,
            'name' => 'required|string|max:255|unique:deposit_types,name,' . $id,
            'description' => 'nullable|string|max:1000',
            'category' => 'nullable|string|max:100',
            'amount' => 'required|numeric|min:0',
            'initial_stock' => 'nullable|integer|min:0',
            'current_stock' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        try {
            $depositType->update($validated);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Database update failed: ' . $e->getMessage()], 500);
        }

        return response()->json($depositType);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $depositType = DepositType::findOrFail($id);

        try {
            $depositType->delete();
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json(['message' => 'Cannot delete deposit type because it is associated with other records.'], 409);
        }

        return response()->json(null, 204);
    }
}
