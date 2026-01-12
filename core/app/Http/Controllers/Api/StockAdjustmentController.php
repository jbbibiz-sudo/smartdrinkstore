<?php
// Chemin: app/Http/Controllers/Api/StockAdjustmentController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StockAdjustmentController extends Controller
{
    /**
     * Ajuste le stock d'un produit (avec conversion d'unités)
     */
    public function adjust(Request $request)
    {
        try {
            $validated = $request->validate([
                'product_id' => 'required|exists:products,id',
                'type' => 'required|in:in,out',
                'quantity' => 'required|integer|min:1',
                'unit_type' => 'required|in:base,retail', // base = casier, retail = bouteille
                'reason' => 'required|string|max:500',
                'reference' => 'nullable|string|max:100',
            ]);

            $product = Product::with(['baseUnit', 'retailUnit'])->findOrFail($validated['product_id']);

            // Calculer la quantité en unité de base (casiers)
            $quantityInBase = $this->convertToBaseUnit(
                $validated['quantity'],
                $validated['unit_type'],
                $product->base_unit_quantity
            );

            // Vérifier le stock pour les sorties
            if ($validated['type'] === 'out' && $product->stock < $quantityInBase) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock insuffisant',
                    'error' => "Stock disponible : {$product->stock} {$product->baseUnit->name}"
                ], 400);
            }

            DB::beginTransaction();

            // Enregistrer le stock précédent
            $previousStock = $product->stock;

            // Ajuster le stock
            if ($validated['type'] === 'in') {
                $product->stock += $quantityInBase;
            } else {
                $product->stock -= $quantityInBase;
            }

            $product->save();

            // Créer le mouvement de stock
            $movement = StockMovement::create([
                'product_id' => $product->id,
                'type' => $validated['type'],
                'quantity' => $quantityInBase,
                'previous_stock' => $previousStock,
                'new_stock' => $product->stock,
                'reason' => $validated['reason'],
                'reference' => $validated['reference'] ?? null,
                'user_id' => auth()->id(),
            ]);

            DB::commit();

            // Recharger le produit avec ses relations
            $product->load(['baseUnit', 'retailUnit', 'category', 'subcategory']);

            return response()->json([
                'success' => true,
                'message' => 'Stock ajusté avec succès',
                'data' => [
                    'product' => $product,
                    'movement' => $movement,
                    'converted_quantity' => [
                        'base' => $quantityInBase,
                        'retail' => $quantityInBase * ($product->base_unit_quantity ?? 1)
                    ]
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur ajustement stock: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'ajustement',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Convertit une quantité en unité de base (casiers)
     */
    private function convertToBaseUnit(int $quantity, string $unitType, ?int $baseUnitQuantity): int
    {
        if ($unitType === 'base') {
            // Déjà en casiers
            return $quantity;
        }

        // Conversion bouteilles → casiers
        if (!$baseUnitQuantity || $baseUnitQuantity === 0) {
            throw new \Exception('Quantité par casier non définie pour ce produit');
        }

        // Arrondir au casier supérieur si nécessaire
        return (int) ceil($quantity / $baseUnitQuantity);
    }

    /**
     * Obtient le stock détaillé d'un produit
     */
    public function getStock($productId)
    {
        try {
            $product = Product::with(['baseUnit', 'retailUnit'])->findOrFail($productId);

            $stockInRetail = $product->stock * ($product->base_unit_quantity ?? 1);

            return response()->json([
                'success' => true,
                'data' => [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'stock_base' => $product->stock,
                    'stock_retail' => $stockInRetail,
                    'base_unit' => $product->baseUnit,
                    'retail_unit' => $product->retailUnit,
                    'base_unit_quantity' => $product->base_unit_quantity,
                    'min_stock' => $product->min_stock,
                    'is_low_stock' => $product->isLowStock(),
                    'is_out_of_stock' => $product->isOutOfStock(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Historique des mouvements d'un produit
     */
    public function movements($productId)
    {
        try {
            $product = Product::findOrFail($productId);

            $movements = StockMovement::where('product_id', $productId)
                ->with(['user'])
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $movements
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 404);
        }
    }
}