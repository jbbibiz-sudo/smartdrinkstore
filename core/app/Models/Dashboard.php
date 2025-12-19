<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class Dashboard extends Model
{
    public static function getStatistics(): array
    {
        $today = Carbon::today();

        $activeProducts = Product::active();

        $totalStockCost = $activeProducts->sum(DB::raw('stock * cost_price'));
        $totalStockSell = $activeProducts->sum(DB::raw('stock * unit_price'));
        $potentialProfit = $totalStockSell - $totalStockCost;

        return [
            'total_products' => $activeProducts->count(),
            'low_stock_count' => $activeProducts->lowStock()->count(),
            'out_of_stock_count' => $activeProducts->where('stock',0)->count(),
            'total_stock_units' => $activeProducts->sum('stock'),
            'total_stock_cost_value' => round($totalStockCost, 2),
            'total_stock_sell_value' => round($totalStockSell, 2),
            'potential_profit' => round($potentialProfit, 2),
            'average_margin_percentage' => $totalStockCost > 0
                ? round(($potentialProfit / $totalStockCost) * 100, 2)
                : 0,
            'today_movements' => StockMovement::whereDate('created_at', $today)->count(),
            'today_stock_in' => StockMovement::whereDate('created_at', $today)->where('type','in')->sum('quantity'),
            'today_stock_out' => StockMovement::whereDate('created_at', $today)->where('type','out')->sum('quantity'),
        ];
    }

    public static function getTopProducts(int $limit = 5, string $type = 'stock'): \Illuminate\Support\Collection
    {
        $query = Product::with('category')->active();

        switch($type) {
            case 'low_stock':
                return $query->lowStock()->orderBy('stock','asc')->limit($limit)->get();
            case 'value':
                return $query->get()->sortByDesc(fn($p)=>$p->stock * $p->unit_price)->take($limit);
            case 'stock':
            default:
                return $query->orderBy('stock','desc')->limit($limit)->get();
        }
    }

    public static function getStockByCategory(): array
    {
        $products = Product::with('category')->active()->get();

        $data = $products->groupBy(fn($p) => $p->category?->name ?? 'Sans catégorie')
            ->map(fn($ps) => [
                'count' => $ps->count(),
                'total_stock' => $ps->sum('stock'),
                'low_stock' => $ps->filter(fn($p)=>$p->is_low_stock)->count(),
                'cost_value' => round($ps->sum(fn($p)=>$p->stock*$p->cost_price),2),
                'sell_value' => round($ps->sum(fn($p)=>$p->stock*$p->unit_price),2),
            ])->values();

        return $data->toArray();
    }

    public static function getRecentMovements(int $limit = 10)
    {
        return StockMovement::with(['product','user'])->latest()->limit($limit)->get();
    }

    public static function getLowStockAlerts(int $limit = 5)
    {
        return Product::with('category')->active()->lowStock()->orderBy('stock','asc')->limit($limit)->get();
    }
}
