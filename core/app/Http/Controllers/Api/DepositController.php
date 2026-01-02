<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Deposit;
use App\Models\DepositReturn;
use App\Models\DepositType;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DepositController extends Controller
{
    /**
     * Liste toutes les consignes
     * GET /api/v1/deposits
     */
    public function index()
    {
        try {
            $deposits = Deposit::with(['depositType', 'customer', 'supplier'])
                ->orderBy('created_at', 'desc')
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $deposits
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Erreur liste consignes: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des consignes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Affiche une consigne
     * GET /api/v1/deposits/{id}
     */
    public function show($id)
    {
        try {
            $deposit = Deposit::with(['depositType', 'customer', 'supplier', 'returns'])
                ->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $deposit
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Erreur affichage consigne: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Consigne non trouvée'
            ], 404);
        }
    }

    /**
     * Statistiques des consignes
     * GET /api/v1/deposits/stats/summary
     */
    public function statistics()
    {
        try {
            $activeDeposits = Deposit::whereIn('status', ['active', 'partially_returned'])->get();
            
            $stats = [
                'active_deposits' => $activeDeposits->count(),
                'total_units_out' => $activeDeposits->sum('quantity_pending') ?? 0,
                'total_deposits_amount' => $activeDeposits->sum('total_deposit_amount') ?? 0,
                'total_penalties' => DepositReturn::sum('total_penalty') ?? 0,
            ];
            
            return response()->json([
                'success' => true,
                'data' => $stats
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Erreur stats consignes: ' . $e->getMessage());
            
            return response()->json([
                'success' => true,
                'data' => [
                    'active_deposits' => 0,
                    'total_units_out' => 0,
                    'total_deposits_amount' => 0,
                    'total_penalties' => 0,
                ]
            ], 200);
        }
    }

    /**
     * Liste tous les retours
     * GET /api/v1/deposit-returns
     */
    public function returns()
    {
        try {
            $returns = DepositReturn::with(['deposit.depositType', 'deposit.customer', 'deposit.supplier'])
                ->orderBy('created_at', 'desc')
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $returns
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Erreur liste retours: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des retours'
            ], 500);
        }
    }

    /**
     * Créer une consigne sortante (vers client)
     * POST /api/v1/deposits/outgoing
     */
    public function storeOutgoing(Request $request)
    {
        try {
            $validated = $request->validate([
                'customer_id' => 'required|exists:customers,id',
                'deposit_type_id' => 'required|exists:deposit_types,id',
                'quantity' => 'required|integer|min:1',
                'unit_deposit_amount' => 'required|numeric|min:0',
                'total_deposit_amount' => 'required|numeric|min:0',
                'notes' => 'nullable|string|max:500',
            ]);

            DB::beginTransaction();

            // Générer une référence unique
            $reference = 'DEP-OUT-' . date('Ymd') . '-' . strtoupper(Str::random(6));

            // Créer la consigne
            $deposit = Deposit::create([
                'reference' => $reference,
                'type' => 'outgoing',
                'customer_id' => $validated['customer_id'],
                'deposit_type_id' => $validated['deposit_type_id'],
                'quantity' => $validated['quantity'],
                'quantity_pending' => $validated['quantity'],
                'quantity_returned' => 0,
                'unit_deposit_amount' => $validated['unit_deposit_amount'],
                'total_deposit_amount' => $validated['total_deposit_amount'],
                'status' => 'active',
                'notes' => $validated['notes'] ?? null,
            ]);

            // Mettre à jour le stock du type d'emballage
            $depositType = DepositType::find($validated['deposit_type_id']);
            if ($depositType) {
                $depositType->decrement('current_stock', $validated['quantity']);
            }

            DB::commit();

            // ✅ CORRECTION TEMPORAIRE : Recharger sans relations pour éviter l'erreur purchases
            $deposit->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Consigne sortante créée avec succès',
                'data' => $deposit
            ], 201);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur création consigne sortante: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Créer une consigne entrante (du fournisseur)
     * POST /api/v1/deposits/incoming
     */
    public function storeIncoming(Request $request)
    {
        try {
            $validated = $request->validate([
                'supplier_id' => 'required|exists:suppliers,id',
                'deposit_type_id' => 'required|exists:deposit_types,id',
                'quantity' => 'required|integer|min:1',
                'unit_deposit_amount' => 'required|numeric|min:0',
                'total_deposit_amount' => 'required|numeric|min:0',
                'notes' => 'nullable|string|max:500',
            ]);

            DB::beginTransaction();

            // Générer une référence unique
            $reference = 'DEP-IN-' . date('Ymd') . '-' . strtoupper(Str::random(6));

            // Créer la consigne
            $deposit = Deposit::create([
                'reference' => $reference,
                'type' => 'incoming',
                'supplier_id' => $validated['supplier_id'],
                'deposit_type_id' => $validated['deposit_type_id'],
                'quantity' => $validated['quantity'],
                'quantity_pending' => $validated['quantity'],
                'quantity_returned' => 0,
                'unit_deposit_amount' => $validated['unit_deposit_amount'],
                'total_deposit_amount' => $validated['total_deposit_amount'],
                'status' => 'active',
                'notes' => $validated['notes'] ?? null,
            ]);

            // Mettre à jour le stock du type d'emballage
            $depositType = DepositType::find($validated['deposit_type_id']);
            if ($depositType) {
                $depositType->increment('current_stock', $validated['quantity']);
            }

            DB::commit();

            // ✅ CORRECTION TEMPORAIRE : Recharger sans relations pour éviter l'erreur purchases
            $deposit->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Consigne entrante créée avec succès',
                'data' => $deposit
            ], 201);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur création consigne entrante: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Traiter un retour d'emballages
     * POST /api/v1/deposits/{id}/return
     */
    public function processReturn(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'quantity' => 'required|integer|min:1',
                'good_condition' => 'required|integer|min:0',
                'damaged' => 'required|integer|min:0',
                'lost' => 'required|integer|min:0',
                'damage_penalty' => 'nullable|numeric|min:0',
                'late_penalty' => 'nullable|numeric|min:0',
                'notes' => 'nullable|string|max:500',
            ]);

            DB::beginTransaction();

            $deposit = Deposit::findOrFail($id);

            // Vérifier que la quantité ne dépasse pas ce qui est en attente
            if ($validated['quantity'] > $deposit->quantity_pending) {
                return response()->json([
                    'success' => false,
                    'message' => 'La quantité retournée dépasse la quantité en attente'
                ], 400);
            }

            // Vérifier que la répartition est correcte
            $total = $validated['good_condition'] + $validated['damaged'] + $validated['lost'];
            if ($total != $validated['quantity']) {
                return response()->json([
                    'success' => false,
                    'message' => 'La répartition ne correspond pas à la quantité totale'
                ], 400);
            }

            // Générer une référence unique pour le retour
            $reference = 'RET-' . date('Ymd') . '-' . strtoupper(Str::random(6));

            // Calculer les montants
            $refundAmount = $validated['good_condition'] * $deposit->unit_deposit_amount;
            $totalPenalty = ($validated['damage_penalty'] ?? 0) + ($validated['late_penalty'] ?? 0);
            $netRefund = max(0, $refundAmount - $totalPenalty);

            // Créer l'enregistrement du retour
            $depositReturn = DepositReturn::create([
                'reference' => $reference,
                'deposit_id' => $deposit->id,
                'quantity_returned' => $validated['quantity'],
                'quantity_good_condition' => $validated['good_condition'],
                'quantity_damaged' => $validated['damaged'],
                'quantity_lost' => $validated['lost'],
                'refund_amount' => $refundAmount,
                'damage_penalty' => $validated['damage_penalty'] ?? 0,
                'delay_penalty' => $validated['late_penalty'] ?? 0,
                'total_penalty' => $totalPenalty,
                'net_refund' => $netRefund,
                'notes' => $validated['notes'] ?? null,
                'returned_at' => now(),
            ]);

            // Mettre à jour la consigne
            $deposit->quantity_returned += $validated['quantity'];
            $deposit->quantity_pending -= $validated['quantity'];

            if ($deposit->quantity_pending == 0) {
                $deposit->status = 'completed';
            } else {
                $deposit->status = 'partially_returned';
            }

            $deposit->save();

            // Mettre à jour le stock (retour des emballages en bon état)
            if ($validated['good_condition'] > 0) {
                $depositType = $deposit->depositType;
                if ($depositType) {
                    if ($deposit->type === 'outgoing') {
                        $depositType->increment('current_stock', $validated['good_condition']);
                    } else {
                        $depositType->decrement('current_stock', $validated['good_condition']);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Retour traité avec succès',
                'data' => $depositReturn->load('deposit')
            ], 200);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur traitement retour: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du traitement: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer une consigne
     * DELETE /api/v1/deposits/{id}
     */
    public function destroy($id)
    {
        try {
            $deposit = Deposit::findOrFail($id);
            
            // Vérifier qu'il n'y a pas de retours associés
            if ($deposit->returns()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de supprimer une consigne avec des retours'
                ], 400);
            }
            
            $deposit->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Consigne supprimée'
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Erreur suppression consigne: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression'
            ], 500);
        }
    }

    /**
     * Consignes en attente
     * GET /api/v1/deposits/pending/list
     */
    public function pending()
    {
        try {
            $pending = Deposit::where('quantity_pending', '>', 0)
                ->with(['depositType', 'customer', 'supplier'])
                ->orderBy('created_at', 'desc')
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $pending
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Erreur consignes en attente: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement'
            ], 500);
        }
    }

    /**
     * Afficher un retour
     * GET /api/v1/deposit-returns/{id}
     */
    public function showReturn($id)
    {
        try {
            $return = DepositReturn::with(['deposit.depositType', 'deposit.customer'])
                ->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $return
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Retour non trouvé'
            ], 404);
        }
    }

    /**
     * Historique des retours d'une consigne
     * GET /api/v1/deposits/{id}/returns
     */
    public function returnHistory($id)
    {
        try {
            $returns = DepositReturn::where('deposit_id', $id)
                ->orderBy('created_at', 'desc')
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $returns
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement'
            ], 500);
        }
    }
}