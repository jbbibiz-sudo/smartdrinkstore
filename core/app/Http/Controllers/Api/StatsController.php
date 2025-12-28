<?php
/**
 * =============================================================================
 * CONTRÔLEUR DES STATISTIQUES
 * =============================================================================
 * Fichier: app/Http/Controllers/Api/StatsController.php
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StatsController extends Controller
{
    /**
     * Statistiques complètes du dashboard
     */
    public function dashboard()
    {
        try {
            // Produits en stock faible
            $lowStockProducts = Product::with('category')
                ->whereRaw('stock <= min_stock')
                ->where('stock', '>', 0)
                ->get();

            // Produits en rupture
            $outOfStockProducts = Product::with('category')
                ->where('stock', 0)
                ->get();

            // Ventes du jour
            $today = now()->startOfDay();
            $todaySales = Sale::where('created_at', '>=', $today)->get();
            
            // Calcul des statistiques
            $stats = [
                // Produits
                'total_products' => Product::count(),
                'active_products' => Product::where('is_active', true)->count(),
                'low_stock_count' => $lowStockProducts->count(),
                'out_of_stock' => $outOfStockProducts->count(),
                'total_stock_value' => Product::selectRaw('SUM(stock * unit_price) as value')
                    ->value('value') ?? 0,
                'total_cost_value' => Product::selectRaw('SUM(stock * cost_price) as value')
                    ->value('value') ?? 0,
                
                // Clients et Fournisseurs
                'total_customers' => Customer::count(),
                'total_suppliers' => Supplier::count(),
                'customers_with_balance' => Customer::where('balance', '>', 0)->count(),
                'total_customer_debt' => Customer::sum('balance') ?? 0,
                
                // Ventes du jour
                'today_sales_count' => $todaySales->count(),
                'today_revenue' => $todaySales->sum('total_amount'),
                'today_cash' => $todaySales->where('payment_method', 'cash')->sum('total_amount'),
                'today_mobile' => $todaySales->where('payment_method', 'mobile')->sum('total_amount'),
                'today_credit' => $todaySales->where('payment_method', 'credit')->sum('total_amount'),
                
                // Alertes détaillées
                'alerts' => [
                    'low_stock' => $lowStockProducts->map(function($product) {
                        return [
                            'id' => $product->id,
                            'name' => $product->name,
                            'sku' => $product->sku,
                            'stock' => $product->stock,
                            'min_stock' => $product->min_stock,
                            'category_name' => $product->category->name ?? 'N/A',
                        ];
                    }),
                    'out_of_stock' => $outOfStockProducts->map(function($product) {
                        return [
                            'id' => $product->id,
                            'name' => $product->name,
                            'sku' => $product->sku,
                            'stock' => 0,
                            'category_name' => $product->category->name ?? 'N/A',
                        ];
                    }),
                ],
            ];
            
            // Ajouter la marge potentielle
            $stats['potential_profit'] = $stats['total_stock_value'] - $stats['total_cost_value'];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur chargement statistiques:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des statistiques',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Statistiques des ventes
     */
    public function sales()
    {
        try {
            $today = now()->startOfDay();
            $thisWeek = now()->startOfWeek();
            $thisMonth = now()->startOfMonth();

            $stats = [
                'today' => [
                    'count' => Sale::where('created_at', '>=', $today)->count(),
                    'total' => Sale::where('created_at', '>=', $today)->sum('total_amount') ?? 0,
                    'cash' => Sale::where('created_at', '>=', $today)
                        ->where('payment_method', 'cash')
                        ->sum('total_amount') ?? 0,
                    'mobile' => Sale::where('created_at', '>=', $today)
                        ->where('payment_method', 'mobile')
                        ->sum('total_amount') ?? 0,
                    'credit' => Sale::where('created_at', '>=', $today)
                        ->where('payment_method', 'credit')
                        ->sum('total_amount') ?? 0,
                ],
                'this_week' => [
                    'count' => Sale::where('created_at', '>=', $thisWeek)->count(),
                    'total' => Sale::where('created_at', '>=', $thisWeek)->sum('total_amount') ?? 0,
                ],
                'this_month' => [
                    'count' => Sale::where('created_at', '>=', $thisMonth)->count(),
                    'total' => Sale::where('created_at', '>=', $thisMonth)->sum('total_amount') ?? 0,
                ],
                'total_sales' => Sale::count(),
                'total_revenue' => Sale::sum('total_amount') ?? 0,
                'total_credit' => Customer::sum('balance') ?? 0,
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur statistiques ventes:', ['message' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des statistiques de ventes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Statistiques des produits
     */
    public function products()
    {
        try {
            $stats = [
                'total' => Product::count(),
                'active' => Product::where('is_active', true)->count(),
                'inactive' => Product::where('is_active', false)->count(),
                'low_stock' => Product::whereRaw('stock <= min_stock')
                    ->where('stock', '>', 0)
                    ->count(),
                'out_of_stock' => Product::where('stock', 0)->count(),
                'by_category' => DB::table('products')
                    ->join('categories', 'products.category_id', '=', 'categories.id')
                    ->select('categories.name', DB::raw('count(*) as count'))
                    ->groupBy('categories.name')
                    ->get(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des statistiques produits',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}