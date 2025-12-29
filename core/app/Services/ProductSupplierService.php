<?php

/**
 * app/Services/ProductSupplierService.php
*/
namespace App\Services; 

use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;

class ProductSupplierService
{
    public function syncSuppliers(int $productId, array $suppliers): void
    {
        $product = Product::findOrFail($productId);
        
        DB::transaction(function() use ($product, $suppliers) {
            // Logique métier complexe ici
            // - Validation des prix
            // - Calcul des marges
            // - Notifications
            // - Logs
            
            $product->suppliers()->sync($this->formatSuppliersData($suppliers));
        });
    }
    
    public function findBestSupplier(int $productId): ?Supplier
    {
        // Logique pour trouver le meilleur fournisseur
        // (prix, délai, fiabilité, etc.)
        return null; // Placeholder for now
    }

    /**
     * Formats the suppliers data for syncing.
     *
     * @param array $suppliers
     * @return array
     */
    private function formatSuppliersData(array $suppliers): array
    {
        return collect($suppliers)->mapWithKeys(function ($supplier) {
            return [$supplier['id'] => ['price' => $supplier['price'], 'is_preferred' => $supplier['is_preferred'] ?? false]];
        })->all();
    }
}