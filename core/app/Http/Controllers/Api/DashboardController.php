<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * GET /api/v1/dashboard/stats
     */
    public function statistics()
    {
        try {
            // Calcul des statistiques
            $totalProducts = Product::count();
            $lowStockCount = Product::whereColumn('stock', '<=', 'min_stock')->count();
            $outOfStock = Product::where('stock', 0)->count();
            $totalStockValue = Product::sum(DB::raw('stock * cost_price'));
            $totalStock = Product::sum('stock');
            $totalConsignmentValue = Product::where('is_consigned', true)
                ->sum(DB::raw('stock * consignment_price'));

            return response()->json([
                'success' => true,
                'data' => [
                    'total_products' => $totalProducts,
                    'low_stock_count' => $lowStockCount,
                    'out_of_stock' => $outOfStock,
                    'total_stock_value' => $totalStockValue,
                    'total_stock' => $totalStock,
                    'total_consignment_value' => $totalConsignmentValue
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des statistiques',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/v1/dashboard
     */
    public function index()
    {
        return $this->statistics();
    }

    /**
     * GET /api/v1/dashboard/charts/stock-evolution
     */
    public function stockEvolution()
    {
        try {
            // À implémenter selon vos besoins
            $data = [];
            
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/v1/dashboard/charts/top-products
     */
    public function topProducts()
    {
        try {
            $products = Product::with('category')
                ->select('*', DB::raw('stock * unit_price as total_value'))
                ->orderBy('total_value', 'DESC')
                ->limit(5)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $products
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/v1/dashboard/charts/low-stock-by-category
     */
    public function lowStockByCategory()
    {
        try {
            $data = Product::with('category')
                ->whereColumn('stock', '<=', 'min_stock')
                ->get()
                ->groupBy('category_id')
                ->map(function ($items) {
                    return [
                        'category' => $items->first()->category->name ?? 'Sans catégorie',
                        'count' => $items->count()
                    ];
                })
                ->values();

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/v1/dashboard/charts/stock-value
     */
    public function stockValue()
    {
        try {
            $data = Category::with(['products' => function ($query) {
                $query->select('id', 'category_id', 'stock', 'cost_price');
            }])
            ->get()
            ->map(function ($category) {
                return [
                    'category' => $category->name,
                    'value' => $category->products->sum(function ($product) {
                        return $product->stock * $product->cost_price;
                    })
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}