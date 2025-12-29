<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class ProductSupplierService
{
    /**
     * Synchronise les fournisseurs d'un produit.
     * 
     * @param int $productId
     * @param array $suppliers Tableau de fournisseurs au format :
     * [
     *   ['id' => 1, 'cost_price' => 1000, 'delivery_days' => 3, 'minimum_order_quantity' => 1, 'is_preferred' => true, 'notes' => '...'],
     *   ...
     * ]
     */
    public function syncSuppliers(int $productId, array $suppliers): void
    {
        $product = Product::findOrFail($productId);

        DB::transaction(function () use ($product, $suppliers) {

            // Validation simple côté service
            foreach ($suppliers as $s) {
                if (!isset($s['id'])) {
                    throw new InvalidArgumentException('Chaque fournisseur doit avoir un id.');
                }
            }

            // Détecter le fournisseur préféré (un seul)
            $preferredCount = collect($suppliers)->where('is_preferred', true)->count();
            if ($preferredCount > 1) {
                // Si plusieurs fournisseurs sont marqués comme préférés, ne garder que le premier
                $firstPreferred = collect($suppliers)->firstWhere('is_preferred', true)['id'];
                foreach ($suppliers as &$s) {
                    $s['is_preferred'] = $s['id'] === $firstPreferred;
                }
            }

            // Préparer les données pour sync
            $syncData = collect($suppliers)->mapWithKeys(function ($s) {
                return [
                    $s['id'] => [
                        'cost_price' => $s['cost_price'] ?? null,
                        'delivery_days' => $s['delivery_days'] ?? null,
                        'minimum_order_quantity' => $s['minimum_order_quantity'] ?? 1,
                        'is_preferred' => $s['is_preferred'] ?? false,
                        'notes' => $s['notes'] ?? null,
                    ]
                ];
            })->all();

            // Exécuter la synchronisation
            $product->suppliers()->sync($syncData);
        });
    }

    /**
     * Retourne le fournisseur préféré pour un produit
     * 
     * @param int $productId
     * @return Supplier|null
     */
    public function findPreferredSupplier(int $productId): ?Supplier
    {
        $product = Product::findOrFail($productId);

        return $product->suppliers()->wherePivot('is_preferred', true)->first();
    }

    /**
     * Retourne le meilleur fournisseur selon une logique métier
     * (ex: prix le plus bas, délai le plus court, etc.)
     * 
     * @param int $productId
     * @return Supplier|null
     */
    public function findBestSupplier(int $productId): ?Supplier
    {
        $product = Product::findOrFail($productId);

        // Exemple : priorité au fournisseur préféré, sinon prix le plus bas
        $preferred = $this->findPreferredSupplier($productId);
        if ($preferred) {
            return $preferred;
        }

        return $product->suppliers()
            ->orderByPivot('cost_price', 'asc')
            ->first();
    }

    /**
     * Ajoute un fournisseur à un produit
     */
    public function attachSupplier(int $productId, array $supplierData): void
    {
        $this->syncSuppliers($productId, [$supplierData]);
    }

    /**
     * Met à jour un fournisseur existant d'un produit
     */
    public function updateSupplier(int $productId, int $supplierId, array $data): void
    {
        $product = Product::findOrFail($productId);
        $currentSuppliers = $product->suppliers->map(function ($s) {
            return [
                'id' => $s->id,
                'cost_price' => $s->pivot->cost_price,
                'delivery_days' => $s->pivot->delivery_days,
                'minimum_order_quantity' => $s->pivot->minimum_order_quantity,
                'is_preferred' => $s->pivot->is_preferred,
                'notes' => $s->pivot->notes,
            ];
        })->toArray();

        // Remplacer ou ajouter les données du fournisseur
        $found = false;
        foreach ($currentSuppliers as &$s) {
            if ($s['id'] === $supplierId) {
                $s = array_merge($s, $data, ['id' => $supplierId]);
                $found = true;
                break;
            }
        }
        if (!$found) {
            $currentSuppliers[] = array_merge(['id' => $supplierId], $data);
        }

        $this->syncSuppliers($productId, $currentSuppliers);
    }

    /**
     * Supprime un fournisseur d'un produit
     */
    public function detachSupplier(int $productId, int $supplierId): void
    {
        $product = Product::findOrFail($productId);

        $remaining = $product->suppliers->reject(fn($s) => $s->id === $supplierId)
            ->map(function ($s) {
                return [
                    'id' => $s->id,
                    'cost_price' => $s->pivot->cost_price,
                    'delivery_days' => $s->pivot->delivery_days,
                    'minimum_order_quantity' => $s->pivot->minimum_order_quantity,
                    'is_preferred' => $s->pivot->is_preferred,
                    'notes' => $s->pivot->notes,
                ];
            })->toArray();

        $this->syncSuppliers($productId, $remaining);
    }
}
