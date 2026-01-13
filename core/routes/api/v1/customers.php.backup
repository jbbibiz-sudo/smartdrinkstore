<?php

// core/routes/api/customers.php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

// ====================================
// CLIENTS
// ====================================

Route::get('/customers', function () {
    try {
        return response()->json([
            'success' => true,
            'data' => DB::table('customers')->orderBy('name')->get()
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors du chargement des clients',
            'error' => $e->getMessage()
        ], 500);
    }
});

Route::post('/customers', function (Request $request) {
    try {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string'
        ]);
        
        $id = DB::table('customers')->insertGetId(array_merge($validated, [
            'balance' => 0,
            'created_at' => now(),
            'updated_at' => now()
        ]));
        
        return response()->json([
            'success' => true,
            'data' => DB::table('customers')->find($id)
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la création du client',
            'error' => $e->getMessage()
        ], 500);
    }
});

Route::put('/customers/{id}', function (Request $request, $id) {
    try {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string'
        ]);
        
        DB::table('customers')->where('id', $id)->update($validated);
        
        return response()->json([
            'success' => true,
            'data' => DB::table('customers')->find($id)
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la mise à jour du client',
            'error' => $e->getMessage()
        ], 500);
    }
});
