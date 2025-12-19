<?php

namespace App\Http\Controllers\Api;

// app/Http/Controllers/API/StockMovementController.php

use App\Models\Supplier;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\Request;

use App\Http\Controllers\Controller;

class StockMovementController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'type' => 'required|in:in,out,consignment_return', // Nouveau type pour retours de consignes
            'reason' => 'required|string',
            'expiry_date' => 'nullable|date', // Nouveau
            'empty_packages' => 'nullable|integer|min:0' // Nouveau
        ]);

        $movement = StockMovement::create($validated);

        // Mise à jour du stock du produit (sauf pour les retours de consignes)
        if ($validated['type'] !== 'consignment_return') {
            $product = Product::find($validated['product_id']);
            if ($validated['type'] === 'in') {
                $product->stock += $validated['quantity'];
            } else {
                $product->stock -= $validated['quantity'];
            }
            $product->save();
        }

        return response()->json($movement, 201);
    }
}