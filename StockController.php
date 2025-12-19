<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockController extends Controller
{
    /**
     * Get stock alerts (low and out of stock).
     */
    public function alerts()
    {
        try {
            $lowStock = DB::table('products')
                ->whereColumn('stock', '<=', 'min_stock')
                ->where('stock', '>', 0)
                ->orderBy('stock', 'asc')
                ->get();

            $outOfStock = DB::table('products')
                ->where('stock', '=', 0)
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'low_stock' => $lowStock,
                    'out_of_stock' => $outOfStock
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erreur lors du chargement des alertes', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Add stock for a product.
     */
    public function add(Request $request)
    {
        DB::beginTransaction();
        try {
            $validated = $request->validate([
                'product_id' => 'required|exists:products,id',
                'quantity' => 'required|integer|min:1',
                'reason' => 'nullable|string|max:255'
            ]);

            $product = DB::table('products')->where('id', $validated['product_id'])->lockForUpdate()->first();

            DB::table('products')
                ->where('id', $validated['product_id'])
                ->increment('stock', $validated['quantity'], ['updated_at' => now()]);

            // This try/catch for a non-existent table is a code smell.
            // It's better to have migrations and assume the table exists.
            // If it's optional, this logic should be handled more gracefully.
            DB::table('stock_movements')->insert([
                'product_id' => $validated['product_id'],
                'type' => 'in',
                'quantity' => $validated['quantity'],
                'reason' => $validated['reason'] ?? 'Réapprovisionnement',
                'created_at' => now()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Stock ajouté avec succès',
                'data' => [
                    'product_id' => $validated['product_id'],
                    'new_stock' => $product->stock + $validated['quantity']
                ]
            ]);
        } catch (ValidationException | \Exception $e) {
            DB::rollBack();
            $statusCode = $e instanceof ValidationException ? 422 : 500;
            return response()->json(['success' => false, 'message' => 'Erreur lors de l\'ajout de stock', 'error' => $e->getMessage()], $statusCode);
        }
    }
}
