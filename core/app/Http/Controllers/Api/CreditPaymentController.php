<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\CreditPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CreditPaymentController extends Controller
{
    /**
     * Liste des ventes à crédit avec leurs paiements
     * GET /api/v1/credits
     */
    public function index(Request $request)
    {
        try {
            $query = Sale::with(['customer', 'creditPayments'])
                ->where('payment_method', 'credit');

            // Filtrer par statut si demandé
            if ($request->has('status')) {
                switch ($request->status) {
                    case 'unpaid':
                        $query->where('paid_amount', 0);
                        break;
                    case 'partial':
                        $query->whereColumn('paid_amount', '<', 'total_amount')
                              ->where('paid_amount', '>', 0);
                        break;
                    case 'overdue':
                        $query->whereColumn('paid_amount', '<', 'total_amount')
                              ->where('due_date', '<', now());
                        break;
                }
            }

            $sales = $query->latest()->get();

            // Formater les données
            $credits = $sales->map(function ($sale) {
                return [
                    'id' => $sale->id,
                    'invoice_number' => $sale->invoice_number,
                    'customer_id' => $sale->customer_id,
                    'customer_name' => $sale->customer->name ?? 'Client comptoir',
                    'sale_date' => $sale->created_at->format('Y-m-d'),
                    'due_date' => $sale->due_date,
                    'credit_days' => $sale->credit_days,
                    'total_amount' => (float) $sale->total_amount,
                    'paid_amount' => (float) $sale->paid_amount,
                    'remaining_amount' => (float) ($sale->total_amount - $sale->paid_amount),
                    'is_overdue' => $sale->due_date && Carbon::parse($sale->due_date)->isPast() && $sale->paid_amount < $sale->total_amount,
                    'payment_count' => $sale->creditPayments->count(),
                    'last_payment_date' => $sale->creditPayments->max('payment_date'),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $credits,
                'count' => $credits->count()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des crédits',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Enregistrer un paiement
     * POST /api/v1/credits/payments
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'sale_id' => 'required|exists:sales,id',
                'amount' => 'required|numeric|min:0.01',
                'payment_method' => 'required|in:cash,mobile,bank_transfer,check',
                'payment_date' => 'required|date',
                'notes' => 'nullable|string|max:500'
            ]);

            DB::beginTransaction();

            // Récupérer la vente
            $sale = Sale::findOrFail($validated['sale_id']);

            // Vérifier que c'est bien une vente à crédit
            if ($sale->payment_method !== 'credit') {
                throw new \Exception('Cette vente n\'est pas à crédit');
            }

            // Calculer le reste à payer
            $remainingAmount = $sale->total_amount - $sale->paid_amount;

            // Vérifier que le montant ne dépasse pas le reste à payer
            if ($validated['amount'] > $remainingAmount) {
                throw new \Exception('Le montant du paiement dépasse le reste à payer');
            }

            // Créer le paiement
            $payment = CreditPayment::create([
                'sale_id' => $validated['sale_id'],
                'amount' => $validated['amount'],
                'payment_method' => $validated['payment_method'],
                'payment_date' => $validated['payment_date'],
                'notes' => $validated['notes'] ?? null,
                'recorded_by' => auth()->id()
            ]);

            // Mettre à jour le montant payé de la vente
            $sale->increment('paid_amount', $validated['amount']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Paiement enregistré avec succès',
                'data' => [
                    'payment' => $payment,
                    'sale' => [
                        'id' => $sale->id,
                        'invoice_number' => $sale->invoice_number,
                        'total_amount' => (float) $sale->total_amount,
                        'paid_amount' => (float) $sale->paid_amount,
                        'remaining_amount' => (float) ($sale->total_amount - $sale->paid_amount)
                    ]
                ]
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);

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
     * Historique des paiements d'une vente
     * GET /api/v1/credits/{saleId}/history
     */
    public function history($saleId)
    {
        try {
            $sale = Sale::with(['customer', 'creditPayments.recordedBy'])
                ->findOrFail($saleId);

            // Vérifier que c'est une vente à crédit
            if ($sale->payment_method !== 'credit') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette vente n\'est pas à crédit'
                ], 400);
            }

            $payments = $sale->creditPayments->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'amount' => (float) $payment->amount,
                    'payment_method' => $payment->payment_method,
                    'payment_date' => $payment->payment_date,
                    'notes' => $payment->notes,
                    'recorded_by' => $payment->recordedBy->name ?? 'Utilisateur supprimé',
                    'created_at' => $payment->created_at->toIso8601String(),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'sale' => [
                        'id' => $sale->id,
                        'invoice_number' => $sale->invoice_number,
                        'customer_name' => $sale->customer->name ?? 'Client comptoir',
                        'total_amount' => (float) $sale->total_amount,
                        'paid_amount' => (float) $sale->paid_amount,
                        'remaining_amount' => (float) ($sale->total_amount - $sale->paid_amount),
                        'due_date' => $sale->due_date,
                    ],
                    'payments' => $payments
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement de l\'historique',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer un paiement
     * DELETE /api/v1/credits/payments/{paymentId}
     */
    public function destroy($paymentId)
    {
        try {
            DB::beginTransaction();

            $payment = CreditPayment::findOrFail($paymentId);
            $sale = $payment->sale;

            // Vérifier si le paiement peut être supprimé (moins de 24h)
            $paymentAge = Carbon::parse($payment->created_at)->diffInHours(now());
            if ($paymentAge > 24) {
                throw new \Exception('Ce paiement ne peut plus être supprimé (plus de 24h)');
            }

            // Décrémenter le montant payé de la vente
            $sale->decrement('paid_amount', $payment->amount);

            // Supprimer le paiement (soft delete)
            $payment->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Paiement supprimé avec succès'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du paiement',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Statistiques des paiements
     * GET /api/v1/credits/statistics
     */
    public function statistics()
    {
        try {
            $totalCredits = Sale::where('payment_method', 'credit')->count();
            $totalAmount = Sale::where('payment_method', 'credit')->sum('total_amount');
            $totalPaid = Sale::where('payment_method', 'credit')->sum('paid_amount');
            $totalRemaining = $totalAmount - $totalPaid;

            // Crédits en retard
            $overdueCredits = Sale::where('payment_method', 'credit')
                ->whereColumn('paid_amount', '<', 'total_amount')
                ->where('due_date', '<', now())
                ->get();

            $overdueCount = $overdueCredits->count();
            $overdueAmount = $overdueCredits->sum(function ($sale) {
                return $sale->total_amount - $sale->paid_amount;
            });

            // Paiements du mois en cours
            $paymentsThisMonth = CreditPayment::whereMonth('payment_date', now()->month)
                ->whereYear('payment_date', now()->year)
                ->sum('amount');

            return response()->json([
                'success' => true,
                'data' => [
                    'total_credits' => $totalCredits,
                    'total_amount' => (float) $totalAmount,
                    'total_paid' => (float) $totalPaid,
                    'total_remaining' => (float) $totalRemaining,
                    'overdue_count' => $overdueCount,
                    'overdue_amount' => (float) $overdueAmount,
                    'payments_this_month' => (float) $paymentsThisMonth
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du calcul des statistiques',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
