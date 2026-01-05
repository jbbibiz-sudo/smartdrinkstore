<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\Product;
use App\Models\Deposit;
use App\Models\DepositType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SaleController extends Controller
{
    /**
     * Liste de toutes les ventes
     * GET /api/v1/sales
     */
    public function index(Request $request)
    {
        try {
            $query = Sale::with(['customer', 'user', 'saleItems.product']);

            // Filtres optionnels
            if ($request->has('type')) {
                $query->where('type', $request->type);
            }

            if ($request->has('payment_method')) {
                $query->where('payment_method', $request->payment_method);
            }

            if ($request->has('customer_id')) {
                $query->where('customer_id', $request->customer_id);
            }

            if ($request->has('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->has('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            $sales = $query->latest()->get();

            // Formater les données
            $formattedSales = $sales->map(function ($sale) {
                return [
                    'id' => $sale->id,
                    'invoice_number' => $sale->invoice_number,
                    'customer_id' => $sale->customer_id,
                    'customer_name' => $sale->customer->name ?? 'Client comptoir',
                    'user_id' => $sale->user_id,
                    'user_name' => $sale->user->name ?? 'Utilisateur supprimé',
                    'type' => $sale->type,
                    'payment_method' => $sale->payment_method,
                    'total_amount' => (float) $sale->total_amount,
                    'discount' => (float) $sale->discount,
                    'paid_amount' => (float) $sale->paid_amount,
                    'due_date' => $sale->due_date?->format('Y-m-d'),
                    'credit_days' => $sale->credit_days,
                    'items_count' => $sale->saleItems->count(),
                    'created_at' => $sale->created_at->toIso8601String(),
                    'updated_at' => $sale->updated_at->toIso8601String(),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedSales,
                'count' => $formattedSales->count()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des ventes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Détails d'une vente
     * GET /api/v1/sales/{id}
     */
    public function show($id)
    {
        try {
            $sale = Sale::with(['customer', 'user', 'saleItems.product'])->findOrFail($id);

            $formattedSale = [
                'id' => $sale->id,
                'invoice_number' => $sale->invoice_number,
                'customer_id' => $sale->customer_id,
                'customer_name' => $sale->customer->name ?? 'Client comptoir',
                'user_id' => $sale->user_id,
                'user_name' => $sale->user->name ?? 'Utilisateur supprimé',
                'type' => $sale->type,
                'payment_method' => $sale->payment_method,
                'total_amount' => (float) $sale->total_amount,
                'discount' => (float) $sale->discount,
                'paid_amount' => (float) $sale->paid_amount,
                'due_date' => $sale->due_date?->format('Y-m-d'),
                'credit_days' => $sale->credit_days,
                'created_at' => $sale->created_at->toIso8601String(),
                'updated_at' => $sale->updated_at->toIso8601String(),
                'items' => $sale->saleItems->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'product_name' => $item->product->name ?? 'Produit supprimé',
                        'product_sku' => $item->product->sku ?? 'N/A',
                        'quantity' => $item->quantity,
                        'unit_price' => (float) $item->unit_price,
                        'subtotal' => (float) $item->subtotal,
                    ];
                })
            ];

            return response()->json([
                'success' => true,
                'data' => $formattedSale
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Vente introuvable',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Créer une nouvelle vente
     * POST /api/v1/sales
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'customer_id' => 'nullable|exists:customers,id',
                'type' => 'required|in:counter,wholesale',
                'payment_method' => 'required|in:cash,mobile,credit',
                'discount' => 'nullable|numeric|min:0',
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.unit_price' => 'required|numeric|min:0',
                'credit_days' => 'nullable|integer|min:1',
                'deposits' => 'nullable|array',
                'deposits.*.deposit_type_id' => 'required_with:deposits|exists:deposit_types,id',
                'deposits.*.quantity' => 'required_with:deposits|integer|min:1',
                'deposit_amount' => 'nullable|numeric|min:0',
            ]);

            DB::beginTransaction();

            // Générer le numéro de facture
            $lastSale = Sale::latest('id')->first();
            $lastNumber = $lastSale ? intval(substr($lastSale->invoice_number, 4)) : 0;
            $invoiceNumber = 'INV-' . str_pad($lastNumber + 1, 6, '0', STR_PAD_LEFT);

            // Calculer le total
            $totalAmount = 0;
            foreach ($validated['items'] as $item) {
                $totalAmount += $item['quantity'] * $item['unit_price'];
            }

            // Appliquer la remise si vente en gros
            $discount = $validated['discount'] ?? 0;
            if ($validated['type'] === 'wholesale' && $discount === 0) {
                $discount = $totalAmount * 0.05; // 5% de remise par défaut
            }

            $finalAmount = $totalAmount - $discount;

            // Calculer la date d'échéance pour les crédits
            $dueDate = null;
            $creditDays = 0; // ✅ Par défaut 0 au lieu de null
            if ($validated['payment_method'] === 'credit') {
                $creditDays = $validated['credit_days'] ?? 30;
                $dueDate = Carbon::now()->addDays($creditDays);
            }

            // Créer la vente
            $sale = Sale::create([
                'invoice_number' => $invoiceNumber,
                'customer_id' => $validated['customer_id'] ?? null,
                'user_id' => auth()->id(),
                'type' => $validated['type'],
                'payment_method' => $validated['payment_method'],
                'total_amount' => $finalAmount,
                'discount' => $discount,
                'deposit_amount' => $validated['deposit_amount'] ?? 0,
                'paid_amount' => $validated['payment_method'] === 'credit' ? 0 : $finalAmount,
                'due_date' => $dueDate,
                'credit_days' => $creditDays, // ✅ Maintenant toujours un entier
            ]);

            // Créer les lignes de vente et mettre à jour le stock
            foreach ($validated['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);

                // Vérifier le stock disponible
                if ($product->stock < $item['quantity']) {
                    throw new \Exception("Stock insuffisant pour {$product->name}");
                }

                // Créer la ligne de vente
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['quantity'] * $item['unit_price'],
                ]);

                // Décrémenter le stock
                $product->decrement('stock', $item['quantity']);

                // Créer le mouvement de stock
                StockMovement::create([
                    'product_id' => $item['product_id'],
                    'type' => 'out',
                    'quantity' => $item['quantity'],
                    'reason' => 'Vente #' . $sale->invoice_number,
                    'reference' => $sale->invoice_number,
                    'user_id' => auth()->id(),
                    'previous_stock' => $product->stock + $item['quantity'],
                    'new_stock' => $product->stock,
                ]);
            }

            // ✅ TRAITER LES CONSIGNES
            if (!empty($validated['deposits'])) {
                foreach ($validated['deposits'] as $depositData) {
                    $depositType = DepositType::findOrFail($depositData['deposit_type_id']);
                    
                    // Vérifier le stock d'emballages
                    if ($depositType->quantity_in_stock < $depositData['quantity']) {
                        throw new \Exception("Stock d'emballages insuffisant pour {$depositType->name}");
                    }

                    // Créer la consigne
                    Deposit::create([
                        'sale_id' => $sale->id,
                        'customer_id' => $sale->customer_id,
                        'deposit_type_id' => $depositData['deposit_type_id'],
                        'quantity' => $depositData['quantity'],
                        'unit_amount' => $depositType->deposit_amount,
                        'total_amount' => $depositData['quantity'] * $depositType->deposit_amount,
                        'status' => 'active',
                        'collected_by' => auth()->id(),
                    ]);

                    // Déduire du stock d'emballages
                    $depositType->decrement('quantity_in_stock', $depositData['quantity']);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Vente créée avec succès',
                'data' => [
                    'id' => $sale->id,
                    'invoice_number' => $sale->invoice_number,
                    'total_amount' => (float) $sale->total_amount,
                    'payment_method' => $sale->payment_method,
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
                'message' => 'Erreur lors de la création de la vente',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer une vente
     * DELETE /api/v1/sales/{id}
     */
    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $sale = Sale::with('saleItems')->findOrFail($id);

            // Vérifier si la vente peut être supprimée (moins de 24h)
            $saleAge = Carbon::parse($sale->created_at)->diffInHours(now());
            if ($saleAge > 24) {
                throw new \Exception('Cette vente ne peut plus être supprimée (plus de 24h)');
            }

            // Restaurer le stock
            foreach ($sale->saleItems as $item) {
                $product = Product::find($item->product_id);
                if ($product) {
                    $product->increment('stock', $item->quantity);
                }
            }

            // Supprimer les lignes de vente
            $sale->saleItems()->delete();

            // Supprimer la vente
            $sale->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Vente supprimée avec succès'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de la vente',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Statistiques des ventes
     * GET /api/v1/sales/stats/summary
     */
    public function stats(Request $request)
    {
        try {
            // Période par défaut: aujourd'hui
            $period = $request->get('period', 'today');
            
            $query = Sale::query();

            // Appliquer les filtres de période
            switch ($period) {
                case 'today':
                    $query->whereDate('created_at', today());
                    break;
                case 'week':
                    $query->whereBetween('created_at', [
                        now()->startOfWeek(),
                        now()->endOfWeek()
                    ]);
                    break;
                case 'month':
                    $query->whereMonth('created_at', now()->month)
                          ->whereYear('created_at', now()->year);
                    break;
                case 'year':
                    $query->whereYear('created_at', now()->year);
                    break;
            }

            $sales = $query->get();

            $total = $sales->sum('total_amount');
            $count = $sales->count();
            $average = $count > 0 ? $total / $count : 0;

            // Stats par type
            $counterSales = $sales->where('type', 'counter')->sum('total_amount');
            $wholesaleSales = $sales->where('type', 'wholesale')->sum('total_amount');

            // Stats par mode de paiement
            $cashSales = $sales->where('payment_method', 'cash')->sum('total_amount');
            $mobileSales = $sales->where('payment_method', 'mobile')->sum('total_amount');
            $creditSales = $sales->where('payment_method', 'credit')->sum('total_amount');

            return response()->json([
                'success' => true,
                'data' => [
                    'period' => $period,
                    'total' => (float) $total,
                    'count' => $count,
                    'average' => (float) $average,
                    'by_type' => [
                        'counter' => (float) $counterSales,
                        'wholesale' => (float) $wholesaleSales,
                    ],
                    'by_payment_method' => [
                        'cash' => (float) $cashSales,
                        'mobile' => (float) $mobileSales,
                        'credit' => (float) $creditSales,
                    ]
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