<?php

namespace App\Http\Controllers\Api;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SaleController extends Controller
{
    /**
     * Liste toutes les ventes
     */
    public function index()
    {
        try {
            $sales = Sale::with(['customer', 'items.product'])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $sales
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur chargement ventes:', ['message' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des ventes'
            ], 500);
        }
    }

    /**
     * ✅ FONCTION AVEC DEBUG AMÉLIORÉ + USER_ID
     */
    public function store(Request $request)
    {
        // ✅ LOG 1: Ce qui arrive du frontend
        Log::info('=== DÉBUT ENREGISTREMENT VENTE ===');
        Log::info('Données brutes reçues:', $request->all());

        try {
            // ✅ VALIDATION COMPLÈTE
            $validated = $request->validate([
                'invoice_number' => 'required|string|unique:sales,invoice_number',
                'customer_id' => 'nullable|exists:customers,id',
                'type' => 'required|in:counter,wholesale',
                'payment_method' => 'required|in:cash,mobile,credit',
                'total_amount' => 'required|numeric|min:0',
                'discount' => 'nullable|numeric|min:0',
                'paid_amount' => 'nullable|numeric|min:0|lte:total_amount',
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.unit_price' => 'required|numeric|min:0',
                'items.*.subtotal' => 'required|numeric|min:0',
            ]);

            // ✅ LOG 2: Après validation
            Log::info('Données validées:', $validated);

            // Vérifier le stock AVANT de commencer la transaction
            foreach ($validated['items'] as $item) {
                $product = Product::find($item['product_id']);
                
                if (!$product) {
                    return response()->json([
                        'success' => false,
                        'message' => "Produit #{$item['product_id']} introuvable"
                    ], 404);
                }
                
                if ($product->stock < $item['quantity']) {
                    return response()->json([
                        'success' => false,
                        'message' => "Stock insuffisant pour {$product->name}. Disponible: {$product->stock}, Demandé: {$item['quantity']}"
                    ], 422);
                }
            }

            // ✅ ACTIVER LES LOGS SQL
            DB::listen(function($query) {
                Log::info('SQL:', [
                    'query' => $query->sql,
                    'bindings' => $query->bindings
                ]);
            });

            // ✅ TRANSACTION POUR GARANTIR LA COHÉRENCE
            DB::beginTransaction();

            try {
                // ✅ LOG 3: Données à insérer (AVEC USER_ID)
                $saleData = [
                    'invoice_number' => $validated['invoice_number'],
                    'customer_id' => $validated['customer_id'],
                    'user_id' => auth()->id(), // ✅ AJOUT DU VENDEUR
                    'type' => $validated['type'],
                    'payment_method' => $validated['payment_method'],
                    'total_amount' => $validated['total_amount'],
                    'discount' => $validated['discount'] ?? 0,
                    'paid_amount' => $validated['paid_amount'] ?? $validated['total_amount'],
                ];

                Log::info('Données à insérer dans Sale::create():', $saleData);
                Log::info('Vendeur (user_id):', auth()->id());
                Log::info('Nom du vendeur:', auth()->user()->name ?? 'Unknown');
                
                // ✅ LOG 4: Vérifier les fillable
                $saleModel = new Sale();
                Log::info('Fillable du modèle Sale:', $saleModel->getFillable());
                Log::info('Guarded du modèle Sale:', $saleModel->getGuarded());

                // ✅ CRÉER LA VENTE
                $sale = Sale::create($saleData);

                Log::info('Vente créée avec succès:', [
                    'id' => $sale->id,
                    'invoice' => $sale->invoice_number,
                    'vendeur' => auth()->user()->name ?? 'Unknown',
                    'all_attributes' => $sale->toArray()
                ]);

                // ✅ CRÉER LES ITEMS ET DÉDUIRE LE STOCK
                foreach ($validated['items'] as $item) {
                    // Créer l'item
                    SaleItem::create([
                        'sale_id' => $sale->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'subtotal' => $item['subtotal'],
                    ]);

                    // Déduire le stock
                    $product = Product::findOrFail($item['product_id']);
                    $product->stock -= $item['quantity'];
                    $product->save();

                    Log::info('Stock déduit:', [
                        'product' => $product->name,
                        'quantity' => $item['quantity'],
                        'nouveau_stock' => $product->stock
                    ]);

                    // Créer le mouvement de stock
                    StockMovement::create([
                        'product_id' => $item['product_id'],
                        'type' => 'out',
                        'quantity' => $item['quantity'],
                        'reason' => "Vente #{$sale->invoice_number}",
                        'user_id' => auth()->id(),
                    ]);
                }

                // ✅ METTRE À JOUR LE SOLDE CLIENT SI CRÉDIT
                if ($validated['payment_method'] === 'credit' && $validated['customer_id']) {
                    $customer = \App\Models\Customer::find($validated['customer_id']);
                    if ($customer) {
                        $unpaid = $validated['total_amount'] - ($validated['paid_amount'] ?? 0);
                        $customer->balance += $unpaid;
                        $customer->save();
                        
                        Log::info('Solde client mis à jour:', [
                            'customer' => $customer->name,
                            'dette_ajoutée' => $unpaid,
                            'nouveau_solde' => $customer->balance
                        ]);
                    }
                }

                DB::commit();

                Log::info('=== VENTE ENREGISTRÉE AVEC SUCCÈS ===');

                // Charger les relations pour la réponse
                $sale->load(['customer', 'items.product', 'user']);

                return response()->json([
                    'success' => true,
                    'message' => 'Vente enregistrée avec succès',
                    'data' => $sale
                ], 201);

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('❌ Erreur dans la transaction:', [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ]);
                throw $e;
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('❌ Validation échouée:', ['errors' => $e->errors()]);
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('❌ ERREUR CRITIQUE:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'enregistrement de la vente',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ COMPLET: Affiche une vente avec toutes les infos (client + produits + VENDEUR)
     * Format optimisé pour les factures
     */
    public function show($id)
    {
        try {
            // ✅ Requête optimisée avec JOINs pour TOUT récupérer (y compris vendeur)
            $sale = DB::table('sales')
                ->leftJoin('customers', 'sales.customer_id', '=', 'customers.id')
                ->leftJoin('users', 'sales.user_id', '=', 'users.id') // ✅ AJOUT VENDEUR
                ->select(
                    'sales.*',
                    // Infos client
                    'customers.name as customer_name',
                    'customers.phone as customer_phone',
                    'customers.email as customer_email',
                    'customers.address as customer_address',
                    // ✅ Infos vendeur
                    'users.name as seller_name',
                    'users.email as seller_email'
                )
                ->where('sales.id', $id)
                ->first();

            if (!$sale) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vente introuvable'
                ], 404);
            }

            // ✅ Récupérer les items avec les noms des produits
            $items = DB::table('sale_items')
                ->join('products', 'sale_items.product_id', '=', 'products.id')
                ->select(
                    'sale_items.id',
                    'sale_items.product_id',
                    'sale_items.quantity',
                    'sale_items.unit_price',
                    'sale_items.subtotal',
                    'products.name as product_name',
                    'products.sku as product_sku'
                )
                ->where('sale_items.sale_id', $id)
                ->get();

            // ✅ Format de réponse optimisé pour le frontend (AVEC VENDEUR)
            return response()->json([
                'success' => true,
                'data' => [
                    // Données de la vente
                    'id' => $sale->id,
                    'invoice_number' => $sale->invoice_number,
                    'customer_id' => $sale->customer_id,
                    'user_id' => $sale->user_id,
                    'type' => $sale->type,
                    'payment_method' => $sale->payment_method,
                    'total_amount' => $sale->total_amount,
                    'discount' => $sale->discount,
                    'paid_amount' => $sale->paid_amount,
                    'created_at' => $sale->created_at,
                    'updated_at' => $sale->updated_at,
                    
                    // Infos client
                    'customer_name' => $sale->customer_name,
                    'customer_phone' => $sale->customer_phone,
                    'customer_email' => $sale->customer_email,
                    'customer_address' => $sale->customer_address,
                    
                    // ✅ Infos vendeur
                    'seller_name' => $sale->seller_name,
                    'seller_email' => $sale->seller_email,
                    
                    // Items avec noms des produits
                    'items' => $items
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur show vente:', [
                'id' => $id,
                'message' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement de la vente'
            ], 500);
        }
    }

    /**
     * Statistiques des ventes
     */
    public function stats()
    {
        try {
            $today = now()->startOfDay();
            $thisWeekStart = now()->startOfWeek();
            $thisMonthStart = now()->startOfMonth();

            $todaySales = Sale::where('created_at', '>=', $today)->get();
            $weekSales = Sale::where('created_at', '>=', $thisWeekStart)->get();
            $monthSales = Sale::where('created_at', '>=', $thisMonthStart)->get();

            // Total crédit impayé
            $totalCredit = Sale::where('payment_method', 'credit')
                ->get()
                ->sum(function ($sale) {
                    return $sale->total_amount - $sale->paid_amount;
                });

            $stats = [
                'today' => [
                    'count' => $todaySales->count(),
                    'total' => $todaySales->sum('total_amount'),
                    'cash' => $todaySales->where('payment_method', 'cash')->sum('total_amount'),
                    'mobile' => $todaySales->where('payment_method', 'mobile')->sum('total_amount'),
                    'credit' => $todaySales->where('payment_method', 'credit')->sum('total_amount'),
                ],
                'this_week' => [
                    'count' => $weekSales->count(),
                    'total' => $weekSales->sum('total_amount'),
                ],
                'this_month' => [
                    'count' => $monthSales->count(),
                    'total' => $monthSales->sum('total_amount'),
                ],
                'total_credit' => $totalCredit
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur stats ventes:', ['message' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du calcul des statistiques'
            ], 500);
        }
    }

    /**
     * Supprime une vente (annulation)
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $sale = Sale::findOrFail($id);

            // Restaurer le stock
            foreach ($sale->items as $item) {
                $product = Product::find($item->product_id);
                if ($product) {
                    $product->stock += $item->quantity;
                    $product->save();

                    // Créer un mouvement
                    StockMovement::create([
                        'product_id' => $item->product_id,
                        'type' => 'in',
                        'quantity' => $item->quantity,
                        'reason' => "Annulation vente #{$sale->invoice_number}",
                        'user_id' => auth()->id(),
                    ]);
                }
            }

            // Ajuster le solde client si crédit
            if ($sale->payment_method === 'credit' && $sale->customer_id) {
                $customer = \App\Models\Customer::find($sale->customer_id);
                if ($customer) {
                    $unpaid = $sale->total_amount - $sale->paid_amount;
                    $customer->balance -= $unpaid;
                    $customer->save();
                }
            }

            // Supprimer la vente (cascade sur items)
            $sale->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Vente annulée avec succès'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur annulation vente:', ['message' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'annulation'
            ], 500);
        }
    }
}
