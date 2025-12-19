<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics.
     */
    public function stats()
    {
        try {
            $totalProducts = DB::table('products')->count();

            $lowStock = DB::table('products')
                ->whereColumn('stock', '<=', 'min_stock')
                ->where('stock', '>', 0)
                ->count();

            $outOfStock = DB::table('products')
                ->where('stock', '=', 0)
                ->count();

            $totalStockValue = DB::table('products')
                ->selectRaw('SUM(stock * unit_price) as total')
                ->value('total') ?? 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'total_products' => $totalProducts,
                    'low_stock_count' => $lowStock,
                    'out_of_stock' => $outOfStock,
                    'total_stock_value' => (float) $totalStockValue
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erreur lors du chargement des statistiques', 'error' => $e->getMessage()], 500);
        }
    }
}