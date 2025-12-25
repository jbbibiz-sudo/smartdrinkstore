<?php
// Chemin: C:\smartdrinkstore\core\app\Http\Controllers\Api\StockController.php
// ✅ VERSION CORRIGÉE - Format de réponse standardisé

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StockController extends Controller
{
    /**
     * Liste les mouvements de stock avec filtres
     */
    public function movements(Request $request)
    {
        try {
            $query = StockMovement::with('product')->latest();
            
            if ($request->has('product_id')) {
                $query->where('product_id', $request->product_id);
            }
            
            if ($request->has('type')) {
                $query->where('type', $request->type);
            }
            
            // Filtres de dates (si fournis)
            if ($request->filled('date_from')) {
                $query->where('created_at', '>=', $request->date_from . ' 00:00:00');
            }
            
            if ($request->filled('date_to')) {
                $query->where('created_at', '<=', $request->date_to . ' 23:59:59');
            }
            
            $movements = $query->limit(100)->get();
            
            // ✅ FORMAT STANDARDISÉ
            return response()->json([
                'success' => true,
                'data' => $movements
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur chargement mouvements', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des mouvements',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crée un nouveau mouvement de stock
     */
    public function createMovement(Request $request)
    {
        try {
            $validated = $request->validate([
                'product_id' => 'required|exists:products,id',
                'type' => 'required|in:in,out,consignment_return',
                'quantity' => 'required|integer|min:1',
                'reason' => 'required|string',
                'expiry_date' => 'nullable|date',
                'empty_packages' => 'nullable|integer|min:0'
            ]);

            $movement = DB::transaction(function() use($validated) {
                $product = Product::findOrFail($validated['product_id']);
                $previous = $product->stock;

                if ($validated['type'] === 'in') {
                    $product->stock += $validated['quantity'];
                } elseif ($validated['type'] === 'out') {
                    if ($previous < $validated['quantity']) {
                        abort(400, 'Stock insuffisant');
                    }
                    $product->stock -= $validated['quantity'];
                }

                $product->save();
                
                return StockMovement::create(array_merge($validated, [
                    'previous_stock' => $previous,
                    'new_stock' => $product->stock,
                    'user_id' => auth()->id()
                ]));
            });

            return response()->json([
                'success' => true,
                'message' => 'Mouvement créé avec succès',
                'data' => $movement->load('product')
            ], 201);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Erreur création mouvement', [
                'message' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du mouvement',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ajoute du stock (endpoint simplifié)
     */
    public function addStock(Request $request)
    {
        try {
            $request->validate([
                'product_id' => 'required|exists:products,id',
                'quantity' => 'required|integer|min:1',
                'reason' => 'nullable|string'
            ]);
            
            $product = Product::findOrFail($request->product_id);
            $product->addStock(
                $request->quantity,
                $request->reason ?? 'Ajout',
                null
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Stock ajouté avec succès',
                'data' => $product->fresh()
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'ajout de stock',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Retire du stock (endpoint simplifié)
     */
    public function removeStock(Request $request)
    {
        try {
            $request->validate([
                'product_id' => 'required|exists:products,id',
                'quantity' => 'required|integer|min:1',
                'reason' => 'nullable|string'
            ]);
            
            $product = Product::findOrFail($request->product_id);
            $product->removeStock(
                $request->quantity,
                $request->reason ?? 'Retrait',
                null
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Stock retiré avec succès',
                'data' => $product->fresh()
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du retrait de stock',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupère les alertes de stock
     */
    public function alerts()
    {
        try {
            $low = Product::with('category')
                ->where('is_active', true)
                ->whereRaw('stock <= min_stock')
                ->where('stock', '>', 0)
                ->orderBy('stock')
                ->get();
            
            $out = Product::with('category')
                ->where('is_active', true)
                ->where('stock', 0)
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'low_stock' => $low,
                    'out_of_stock' => $out,
                    'low_stock_count' => $low->count(),
                    'out_of_stock_count' => $out->count()
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Valorisation du stock
     */
    public function stockValuation()
    {
        try {
            $products = Product::with('category')
                ->where('is_active', true)
                ->get();
            
            $valuation = [
                'total_units' => $products->sum('stock'),
                'total_cost_value' => $products->sum(fn($p) => $p->stock * $p->cost_price),
                'total_sell_value' => $products->sum(fn($p) => $p->stock * $p->unit_price),
                'by_category' => $products->groupBy('category.name')->map(fn($ps, $cat) => [
                    'count' => $ps->count(),
                    'total_units' => $ps->sum('stock'),
                    'cost_value' => $ps->sum(fn($p) => $p->stock * $p->cost_price),
                    'sell_value' => $ps->sum(fn($p) => $p->stock * $p->unit_price),
                ]),
            ];
            
            $valuation['potential_profit'] = $valuation['total_sell_value'] - $valuation['total_cost_value'];
            
            return response()->json([
                'success' => true,
                'data' => $valuation
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Rapport de statut du stock
     */
    public function stockStatusReport()
    {
        try {
            $products = Product::with('category')
                ->where('is_active', true)
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'total_products' => $products->count(),
                    'in_stock' => $products->where('stock', '>', 0)->count(),
                    'out_of_stock' => $products->where('stock', 0)->count(),
                    'low_stock' => $products->filter(fn($p) => $p->stock <= $p->min_stock && $p->stock > 0)->count(),
                    'overstocked' => $products->where('stock', '>', 100)->count()
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}