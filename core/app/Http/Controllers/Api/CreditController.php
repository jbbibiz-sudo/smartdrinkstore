<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\CreditPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CreditController extends Controller
{
    /**
     * Liste toutes les ventes à crédit
     */
    public function index(Request $request)
    {
        $query = Sale::with(['customer', 'user', 'creditPayments'])
                    ->where('payment_method', 'credit');

        // Filtres
        if ($request->has('status')) {
            if ($request->status === 'paid') {
                $query->whereColumn('paid_amount', '>=', 'total_amount');
            } elseif ($request->status === 'partial') {
                $query->where('paid_amount', '>', 0)
                      ->whereColumn('paid_amount', '<', 'total_amount');
            } elseif ($request->status === 'unpaid') {
                $query->where('paid_amount', 0);
            }
        }

        if ($request->has('overdue')) {
            $query->where('due_date', '<', now())
                  ->whereColumn('paid_amount', '<', 'total_amount');
        }

        $credits = $query->latest()->get();

        // Ajouter les montants calculés
        $credits->each(function($sale) {
            $sale->remaining_amount = $sale->total_amount - $sale->paid_amount;
            $sale->is_overdue = $sale->is_overdue;
            $sale->is_fully_paid = $sale->is_fully_paid;
        });

        return response()->json([
            'success' => true,
            'data' => $credits,
            'summary' => [
                'total_credits' => $credits->count(),
                'total_amount' => $credits->sum('total_amount'),
                'total_paid' => $credits->sum('paid_amount'),
                'total_remaining' => $credits->sum('remaining_amount'),
                'overdue_count' => $credits->where('is_overdue', true)->count(),
            ]
        ]);
    }

    /**
     * Enregistrer un paiement de crédit
     */
    public function storePayment(Request $request)
    {
        $validated = $request->validate([
            'sale_id' => 'required|exists:sales,id',
            'amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:cash,mobile_money,bank_transfer',
            'reference' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $sale = Sale::findOrFail($validated['sale_id']);

            // Vérifier que la vente est bien à crédit
            if ($sale->payment_method !== 'credit') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette vente n\'est pas à crédit'
                ], 400);
            }

            // Vérifier que le montant ne dépasse pas le reste dû
            $remaining = $sale->total_amount - $sale->paid_amount;
            if ($validated['amount'] > $remaining) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le montant du paiement dépasse le reste dû'
                ], 400);
            }

            // Créer le paiement
            $payment = CreditPayment::create($validated);

            // Mettre à jour le montant payé de la vente
            $sale->increment('paid_amount', $validated['amount']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Paiement enregistré avec succès',
                'data' => $payment,
                'sale' => $sale->fresh(['customer', 'user'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'enregistrement du paiement',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Afficher une vente à crédit spécifique
     */
    public function show(Sale $sale)
    {
        if ($sale->payment_method !== 'credit') {
            return response()->json([
                'success' => false,
                'message' => 'Cette vente n\'est pas à crédit'
            ], 400);
        }

        $sale->load(['customer', 'user', 'saleItems.product', 'creditPayments']);
        $sale->remaining_amount = $sale->remaining_amount;
        $sale->is_overdue = $sale->is_overdue;

        return response()->json([
            'success' => true,
            'data' => $sale
        ]);
    }
}