<?php

// Chemin: app/Http/Controllers/Api/ProductUnitController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductUnit;
use Illuminate\Http\Request;

class ProductUnitController extends Controller
{
    /**
     * Liste toutes les unités actives
     */
    public function index()
    {
        try {
            $units = ProductUnit::active()->orderBy('name')->get();

            return response()->json([
                'success' => true,
                'data' => $units
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Affiche une unité
     */
    public function show($id)
    {
        try {
            $unit = ProductUnit::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $unit
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 404);
        }
    }
}