<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Deposit;
use App\Models\DepositReturn;
use App\Models\DepositType;
use Illuminate\Support\Facades\Log;

class DepositController extends Controller
{
    /**
     * Statistiques des consignes - VERSION CORRIGÉE
     * GET /api/v1/deposits/stats/summary
     */
    public function statistics()
    {
        try {
            // Consignes actives
            $activeDeposits = Deposit::whereIn('status', ['active', 'partially_returned'])->get();
            
            $stats = [
                'active_deposits' => $activeDeposits->count(),
                'total_units_out' => $activeDeposits->sum('quantity_pending') ?? 0,
                'total_deposits_amount' => $activeDeposits->sum('total_deposit_amount') ?? 0,
                'total_penalties' => DepositReturn::sum('total_penalty') ?? 0,
            ];
            
            return response()->json(['data' => $stats], 200);
            
        } catch (\Exception $e) {
            Log::error('Erreur stats consignes: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            
            // Retourner des valeurs par défaut
            return response()->json([
                'data' => [
                    'active_deposits' => 0,
                    'total_units_out' => 0,
                    'total_deposits_amount' => 0,
                    'total_penalties' => 0,
                ]
            ], 200);
        }
    }
}