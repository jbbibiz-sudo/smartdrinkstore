<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PurchaseController extends Controller
{
    /**
     * Liste tous les achats
     * GET /api/v1/purchases
     */
    public function index(Request $request)
    {
        try {
            $query = Purchase::with(['supplier', 'user', 'items.product']);

            // Filtres
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('supplier_id')) {
                $query->where('supplier_id', $request->supplier_id);
            }

            if ($request->has('date_from')) {
                $query->whereDate('order_date', '>=', $request->date_from);
            }

            if ($request->has('date_to')) {
                $query->whereDate('order_date', '<=', $request->date_to);
            }

            $purchases = $query->orderBy('created_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => $purchases
            ], 200);

        } catch (\Exception $e) {
            Log::error('Erreur liste achats: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des achats',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Affiche un achat
     * GET /api/v1/purchases/{id}
     */
    public function show($id)
    {
        try {
            $purchase = Purchase::with([
                'supplier',
                'user',
                'items.product',
                'items.depositType',
                'deposits'
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $purchase
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Achat non trouvé'
            ], 404);
        }
    }

    /**
     * Créer un nouvel achat
     * POST /api/v1/purchases
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'supplier_id' => 'required|exists:suppliers,id',
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.unit_cost' => 'required|numeric|min:0',
                'items.*.is_consigned' => 'nullable|boolean',
                'items.*.deposit_type_id' => 'nullable|exists:deposit_types,id',
                'items.*.deposit_quantity' => 'nullable|integer|min:0',
                'items.*.unit_deposit_amount' => 'nullable|numeric|min:0',
                'items.*.notes' => 'nullable|string|max:500',
                'payment_method' => 'required|in:cash,mobile,credit,bank_transfer',
                'mobile_operator' => 'nullable|required_if:payment_method,mobile|string|max:50',
                'mobile_reference' => 'nullable|required_if:payment_method,mobile|string|max:100',
                'paid_amount' => 'nullable|numeric|min:0',
                'credit_days' => 'nullable|integer|min:1',
                'discount' => 'nullable|numeric|min:0',
                'tax' => 'nullable|numeric|min:0',
                'order_date' => 'nullable|date',
                'expected_delivery_date' => 'nullable|date',
                'notes' => 'nullable|string|max:1000',
            ]);

            DB::beginTransaction();

            // Calculer le sous-total
            $subtotal = 0;
            $totalDepositAmount = 0;
            $hasDeposits = false;

            foreach ($validated['items'] as $itemData) {
                $subtotal += $itemData['quantity'] * $itemData['unit_cost'];
                
                if (isset($itemData['is_consigned']) && $itemData['is_consigned']) {
                    $hasDeposits = true;
                    $totalDepositAmount += ($itemData['deposit_quantity'] ?? 0) * ($itemData['unit_deposit_amount'] ?? 0);
                }
            }

            $tax = $validated['tax'] ?? 0;
            $discount = $validated['discount'] ?? 0;
            $totalAmount = $subtotal + $tax - $discount;

            // Calculer la date d'échéance si crédit
            $dueDate = null;
            if ($validated['payment_method'] === 'credit' && isset($validated['credit_days'])) {
                $orderDate = $validated['order_date'] ?? now();
                $dueDate = date('Y-m-d', strtotime($orderDate . ' + ' . $validated['credit_days'] . ' days'));
            }

            // Créer l'achat
            $purchase = Purchase::create([
                'supplier_id' => $validated['supplier_id'],
                'user_id' => auth()->id(),
                'status' => 'draft',
                'subtotal' => $subtotal,
                'tax' => $tax,
                'discount' => $discount,
                'total_amount' => $totalAmount,
                'payment_method' => $validated['payment_method'],
                'mobile_operator' => $validated['mobile_operator'] ?? null,
                'mobile_reference' => $validated['mobile_reference'] ?? null,
                'paid_amount' => $validated['paid_amount'] ?? 0,
                'credit_days' => $validated['credit_days'] ?? null,
                'due_date' => $dueDate,
                'total_deposit_amount' => $totalDepositAmount,
                'has_deposits' => $hasDeposits,
                'order_date' => $validated['order_date'] ?? now(),
                'expected_delivery_date' => $validated['expected_delivery_date'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            // Créer les lignes d'achat
            foreach ($validated['items'] as $itemData) {
                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $itemData['product_id'],
                    'quantity' => $itemData['quantity'],
                    'quantity_received' => 0,
                    'unit_cost' => $itemData['unit_cost'],
                    'is_consigned' => $itemData['is_consigned'] ?? false,
                    'deposit_type_id' => $itemData['deposit_type_id'] ?? null,
                    'deposit_quantity' => $itemData['deposit_quantity'] ?? null,
                    'unit_deposit_amount' => $itemData['unit_deposit_amount'] ?? null,
                    'notes' => $itemData['notes'] ?? null,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Achat créé avec succès',
                'data' => $purchase->load(['supplier', 'items.product'])
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur création achat: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Confirmer un achat
     * POST /api/v1/purchases/{id}/confirm
     */
    public function confirm($id)
    {
        try {
            $purchase = Purchase::findOrFail($id);

            if ($purchase->status !== 'draft') {
                return response()->json([
                    'success' => false,
                    'message' => 'Seuls les achats en brouillon peuvent être confirmés'
                ], 400);
            }

            $purchase->status = 'confirmed';
            $purchase->save();

            return response()->json([
                'success' => true,
                'message' => 'Achat confirmé',
                'data' => $purchase
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la confirmation'
            ], 500);
        }
    }

    /**
     * Réceptionner un achat
     * POST /api/v1/purchases/{id}/receive
     */
    public function receive(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'items' => 'required|array|min:1',
                'items.*.id' => 'required|exists:purchase_items,id',
                'items.*.quantity_received' => 'required|integer|min:0',
                'received_date' => 'nullable|date',
            ]);

            DB::beginTransaction();

            $purchase = Purchase::with('items')->findOrFail($id);

            if (!in_array($purchase->status, ['confirmed', 'draft'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cet achat ne peut pas être réceptionné'
                ], 400);
            }

            // Mettre à jour les quantités reçues
            foreach ($validated['items'] as $itemData) {
                $item = PurchaseItem::findOrFail($itemData['id']);
                
                if ($item->purchase_id !== $purchase->id) {
                    throw new \Exception('Ligne d\'achat invalide');
                }

                if ($itemData['quantity_received'] > $item->quantity) {
                    throw new \Exception('Quantité reçue supérieure à la quantité commandée');
                }

                $item->quantity_received = $itemData['quantity_received'];
                $item->save();
            }

            // Marquer comme reçu
            $purchase->status = 'received';
            $purchase->received_date = $validated['received_date'] ?? now();
            $purchase->save();

            // Mettre à jour le stock
            $purchase->updateStock();

            // ✅ CORRECTION : Passer explicitement le user_id
            if ($purchase->has_deposits) {
                $purchase->createDeposits(auth()->id()); // ✅ ICI
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Achat réceptionné avec succès',
                'data' => $purchase->fresh(['supplier', 'items.product'])
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur réception achat: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la réception: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Annuler un achat
     * POST /api/v1/purchases/{id}/cancel
     */
    public function cancel($id)
    {
        try {
            $purchase = Purchase::findOrFail($id);

            if ($purchase->status === 'received') {
                return response()->json([
                    'success' => false,
                    'message' => 'Un achat réceptionné ne peut pas être annulé'
                ], 400);
            }

            $purchase->status = 'cancelled';
            $purchase->save();

            return response()->json([
                'success' => true,
                'message' => 'Achat annulé',
                'data' => $purchase
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'annulation'
            ], 500);
        }
    }

    /**
     * Supprimer un achat
     * DELETE /api/v1/purchases/{id}
     */
    public function destroy($id)
    {
        try {
            $purchase = Purchase::findOrFail($id);

            if ($purchase->status !== 'draft') {
                return response()->json([
                    'success' => false,
                    'message' => 'Seuls les achats en brouillon peuvent être supprimés'
                ], 400);
            }

            $purchase->delete();

            return response()->json([
                'success' => true,
                'message' => 'Achat supprimé'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression'
            ], 500);
        }
    }

    /**
     * Statistiques des achats
     * GET /api/v1/purchases/stats/summary
     */
    public function statistics()
    {
        try {
            $stats = [
                'total_purchases' => Purchase::count(),
                'draft' => Purchase::draft()->count(),
                'confirmed' => Purchase::confirmed()->count(),
                'received' => Purchase::received()->count(),
                'total_amount' => Purchase::received()->sum('total_amount'),
                'pending_amount' => Purchase::where('payment_method', 'credit')
                    ->whereColumn('paid_amount', '<', 'total_amount')
                    ->sum(DB::raw('total_amount - paid_amount')),
                'overdue_purchases' => Purchase::overdue()->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ], 200);

        } catch (\Exception $e) {
            Log::error('Erreur stats achats: ' . $e->getMessage());

            return response()->json([
                'success' => true,
                'data' => [
                    'total_purchases' => 0,
                    'draft' => 0,
                    'confirmed' => 0,
                    'received' => 0,
                    'total_amount' => 0,
                    'pending_amount' => 0,
                    'overdue_purchases' => 0,
                ]
            ], 200);
        }
    }
}
