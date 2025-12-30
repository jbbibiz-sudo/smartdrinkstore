<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\CreditPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CreditPaymentController extends Controller
{
    /**
     * Liste des ventes à crédit avec leur statut de paiement
     */
    public function index(Request $request)
    {
        try {
            $query = Sale::with(['customer', 'creditPayments'])
                ->where('payment_method', 'credit');

            // Filtres
            if ($request->has('status')) {
                switch ($request->status) {
                    case 'unpaid':
                        $query->where('paid_amount', 0);
                        break;
                    case 'partial':
                        $query->where('paid_amount', '>', 0)
                              ->whereColumn('paid_amount', '<', 'total_amount');
                        break;
                    case 'paid':
                        $query->whereColumn('paid_amount', '>=', 'total_amount');
                        break;
                    case 'overdue':
                        $query->where('due_date', '<', now())
                              ->whereColumn('paid_amount', '<', 'total_amount');
                        break;
                }
            }

            if ($request->has('customer_id')) {
                $query->where('customer_id', $request->customer_id);
            }

            // Tri
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            $sales = $query->get();

            // Enrichir les données avec calculs
            $credits = $sales->map(function ($sale) {
                $remainingAmount = $sale->total_amount - $sale->paid_amount;
                $isOverdue = $sale->due_date && $sale->due_date < now() && $remainingAmount > 0;

                return [
                    'id' => $sale->id,
                    'invoice_number' => $sale->invoice_number,
                    'customer_id' => $sale->customer_id,
                    'customer_name' => $sale->customer->name ?? 'N/A',
                    'sale_date' => $sale->created_at->format('Y-m-d'),
                    'due_date' => $sale->due_date?->format('Y-m-d'),
                    'total_amount' => (float) $sale->total_amount,
                    'paid_amount' => (float) $sale->paid_amount,
                    'remaining_amount' => (float) $remainingAmount,
                    'is_overdue' => $isOverdue,
                    'payment_count' => $sale->creditPayments->count(),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $credits,
                'statistics' => [
                    'total_debt' => $credits->sum('remaining_amount'),
                    'overdue_debt' => $credits->where('is_overdue', true)->sum('remaining_amount'),
                    'overdue_count' => $credits->where('is_overdue', true)->count(),
                    'current_debt' => $credits->where('is_overdue', false)->sum('remaining_amount'),
                    'current_count' => $credits->where('is_overdue', false)->where('remaining_amount', '>', 0)->count(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des crédits',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Enregistrer un nouveau paiement
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sale_id' => 'required|exists:sales,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,mobile,bank_transfer,check',
            'payment_date' => 'required|date|before_or_equal:today',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation échouée',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $sale = Sale::findOrFail($request->sale_id);

            // Vérifier que la vente est bien à crédit
            if ($sale->payment_method !== 'credit') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette vente n\'est pas à crédit'
                ], 400);
            }

            // Vérifier que le montant ne dépasse pas le solde restant
            $remainingAmount = $sale->total_amount - $sale->paid_amount;
            if ($request->amount > $remainingAmount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le montant du paiement dépasse le solde restant',
                    'remaining_amount' => $remainingAmount
                ], 400);
            }

            // Créer le paiement
            $payment = CreditPayment::create([
                'sale_id' => $request->sale_id,
                'amount' => $request->amount,
                'payment_method' => $request->payment_method,
                'payment_date' => $request->payment_date,
                'notes' => $request->notes,
                'recorded_by' => auth()->id(),
            ]);

            // Mettre à jour le montant payé de la vente
            $sale->paid_amount += $request->amount;
            $sale->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Paiement enregistré avec succès',
                'data' => [
                    'payment' => $payment,
                    'new_paid_amount' => (float) $sale->paid_amount,
                    'remaining_amount' => (float) ($sale->total_amount - $sale->paid_amount),
                    'is_fully_paid' => $sale->paid_amount >= $sale->total_amount
                ]
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
     * Historique des paiements d'une vente
     */
    public function history($saleId)
    {
        try {
            $sale = Sale::with(['creditPayments.recordedBy'])->findOrFail($saleId);

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
                    'payment_date' => $payment->payment_date->format('Y-m-d'),
                    'notes' => $payment->notes,
                    'recorded_by' => $payment->recordedBy->name ?? 'N/A',
                    'created_at' => $payment->created_at->toISOString(),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'sale' => [
                        'id' => $sale->id,
                        'invoice_number' => $sale->invoice_number,
                        'total_amount' => (float) $sale->total_amount,
                        'paid_amount' => (float) $sale->paid_amount,
                        'remaining_amount' => (float) ($sale->total_amount - $sale->paid_amount),
                    ],
                    'payments' => $payments
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de l\'historique',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer un paiement (annulation)
     */
    public function destroy($paymentId)
    {
        try {
            DB::beginTransaction();

            $payment = CreditPayment::findOrFail($paymentId);
            $sale = $payment->sale;

            // Vérifier que le paiement est récent (< 24h)
            if ($payment->created_at->diffInHours(now()) > 24) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de supprimer un paiement de plus de 24h'
                ], 403);
            }

            // Mettre à jour le montant payé de la vente
            $sale->paid_amount -= $payment->amount;
            $sale->save();

            // Supprimer le paiement (soft delete)
            $payment->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Paiement supprimé avec succès',
                'data' => [
                    'new_paid_amount' => (float) $sale->paid_amount,
                    'remaining_amount' => (float) ($sale->total_amount - $sale->paid_amount),
                ]
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
     */
    public function statistics(Request $request)
    {
        try {
            $startDate = $request->get('start_date', now()->startOfMonth());
            $endDate = $request->get('end_date', now()->endOfMonth());

            $payments = CreditPayment::inPeriod($startDate, $endDate)->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'total_collected' => $payments->sum('amount'),
                    'payment_count' => $payments->count(),
                    'by_method' => [
                        'cash' => $payments->where('payment_method', 'cash')->sum('amount'),
                        'mobile' => $payments->where('payment_method', 'mobile')->sum('amount'),
                        'bank_transfer' => $payments->where('payment_method', 'bank_transfer')->sum('amount'),
                        'check' => $payments->where('payment_method', 'check')->sum('amount'),
                    ],
                    'period' => [
                        'start' => $startDate,
                        'end' => $endDate,
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}