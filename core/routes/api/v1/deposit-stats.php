<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

// Routes statistiques des consignes - déjà dans le groupe prefix('v1')
// Pas besoin de Route::prefix('deposits') ici

// Statistiques générales des consignes
Route::get('/deposits/stats/summary', function () {
    try {
        // Statistiques des consignes
        $stats = [
            // Total des consignes en cours
            'total_active' => DB::table('deposits')
                ->where('status', 'active')
                ->count(),
            
            // Total des consignes retournées
            'total_returned' => DB::table('deposit_returns')
                ->count(),
            
            // Valeur totale des consignes en cours
            'total_value' => DB::table('deposits')
                ->where('status', 'active')
                ->join('deposit_types', 'deposits.deposit_type_id', '=', 'deposit_types.id')
                ->sum(DB::raw('deposits.quantity * deposit_types.amount')),
            
            // Nombre de consignes par type
            'by_type' => DB::table('deposits')
                ->where('status', 'active')
                ->join('deposit_types', 'deposits.deposit_type_id', '=', 'deposit_types.id')
                ->select(
                    'deposit_types.name',
                    'deposit_types.amount',
                    DB::raw('COUNT(deposits.id) as count'),
                    DB::raw('SUM(deposits.quantity) as total_quantity'),
                    DB::raw('SUM(deposits.quantity * deposit_types.amount) as total_value')
                )
                ->groupBy('deposit_types.id', 'deposit_types.name', 'deposit_types.amount')
                ->get(),
            
            // Consignes en retard (plus de 30 jours)
            'overdue' => DB::table('deposits')
                ->where('status', 'active')
                ->where('created_at', '<', now()->subDays(30))
                ->count(),
            
            // Statistiques récentes (7 derniers jours)
            'recent' => [
                'new_deposits' => DB::table('deposits')
                    ->where('created_at', '>=', now()->subDays(7))
                    ->count(),
                
                'returns' => DB::table('deposit_returns')
                    ->where('created_at', '>=', now()->subDays(7))
                    ->count(),
            ]
        ];
        
        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors du chargement des statistiques des consignes',
            'error' => $e->getMessage()
        ], 500);
    }
});

// Statistiques par client
Route::get('/deposits/stats/by-customer', function () {
    try {
        $stats = DB::table('deposits')
            ->where('deposits.status', 'active')
            ->join('customers', 'deposits.customer_id', '=', 'customers.id')
            ->join('deposit_types', 'deposits.deposit_type_id', '=', 'deposit_types.id')
            ->select(
                'customers.id',
                'customers.name',
                DB::raw('COUNT(deposits.id) as deposit_count'),
                DB::raw('SUM(deposits.quantity) as total_quantity'),
                DB::raw('SUM(deposits.quantity * deposit_types.amount) as total_value')
            )
            ->groupBy('customers.id', 'customers.name')
            ->orderBy('total_value', 'desc')
            ->limit(20)
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors du chargement des statistiques par client',
            'error' => $e->getMessage()
        ], 500);
    }
});

// Statistiques par type de consigne
Route::get('/deposits/stats/by-type', function () {
    try {
        $stats = DB::table('deposit_types')
            ->leftJoin('deposits', function($join) {
                $join->on('deposit_types.id', '=', 'deposits.deposit_type_id')
                     ->where('deposits.status', '=', 'active');
            })
            ->select(
                'deposit_types.id',
                'deposit_types.name',
                'deposit_types.amount',
                'deposit_types.description',
                DB::raw('COALESCE(COUNT(deposits.id), 0) as active_deposits'),
                DB::raw('COALESCE(SUM(deposits.quantity), 0) as total_quantity'),
                DB::raw('COALESCE(SUM(deposits.quantity * deposit_types.amount), 0) as total_value')
            )
            ->groupBy('deposit_types.id', 'deposit_types.name', 'deposit_types.amount', 'deposit_types.description')
            ->orderBy('total_value', 'desc')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors du chargement des statistiques par type',
            'error' => $e->getMessage()
        ], 500);
    }
});

// Historique des mouvements de consignes (timeline)
Route::get('/deposits/stats/timeline', function () {
    try {
        // Récupérer les 30 derniers jours
        $timeline = [];
        
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            
            $deposits = DB::table('deposits')
                ->whereDate('created_at', $date)
                ->count();
            
            $returns = DB::table('deposit_returns')
                ->whereDate('created_at', $date)
                ->count();
            
            $timeline[] = [
                'date' => $date,
                'deposits' => $deposits,
                'returns' => $returns,
            ];
        }
        
        return response()->json([
            'success' => true,
            'data' => $timeline
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors du chargement de la timeline',
            'error' => $e->getMessage()
        ], 500);
    }
});
