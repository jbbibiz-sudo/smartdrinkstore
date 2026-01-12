<?php

// Chemin: app/Models/Product.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\PurchaseItem;
use App\Models\ProductUnit;
use App\Models\StockMovement;
use App\Models\SaleItem;
use App\Models\Supplier;

class Product extends Model
{
    protected $fillable = [
        'name',
        'sku',
        'code',
        'barcode',
        'category_id',
        'subcategory_id',
        'brand',
        'volume',
        'unit_price',
        'cost_price',
        'stock',
        'min_stock',
        'description',
        'is_consigned',
        'consignment_price',
        'empty_containers_stock',
        'is_active',
        // ✅ NOUVEAUX CHAMPS UNITÉS
        'base_unit_id',
        'base_unit_volume',
        'base_unit_volume_unit',
        'base_unit_quantity',
        'retail_unit_id',
        'retail_stock_remainder',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'consignment_price' => 'decimal:2',
        'stock' => 'integer',
        'min_stock' => 'integer',
        'empty_containers_stock' => 'integer',
        'is_consigned' => 'boolean',
        'is_active' => 'boolean',
        'base_unit_volume' => 'decimal:3',
        'base_unit_quantity' => 'integer',
        'retail_stock_remainder' => 'integer',
    ];

    /**
     * ✅ NOUVEAU : Relation avec l'unité de base (casier/pack)
     */
    public function baseUnit(): BelongsTo
    {
        return $this->belongsTo(ProductUnit::class, 'base_unit_id');
    }

    /**
     * ✅ NOUVEAU : Relation avec l'unité de détail (bouteille/canette)
     */
    public function retailUnit(): BelongsTo
    {
        return $this->belongsTo(ProductUnit::class, 'retail_unit_id');
    }

    // ✅ NOUVEAUX ACCESSORS pour stock avec reste
    
    /**
     * Stock total en unités de détail (bouteilles)
     */
    public function getTotalRetailStockAttribute(): int
    {
        $baseQty = $this->base_unit_quantity ?? 1;
        return ($this->stock * $baseQty) + ($this->retail_stock_remainder ?? 0);
    }

    /**
     * Stock en casiers complets
     */
    public function getFullCasesAttribute(): int
    {
        return $this->stock;
    }

    /**
     * Bouteilles restantes (casier entamé)
     */
    public function getLooseUnitsAttribute(): int
    {
        return $this->retail_stock_remainder ?? 0;
    }

    /**
     * Affichage formaté du stock
     * Ex: "10 casiers + 5 bouteilles" ou "10 casiers"
     */
    public function getFormattedStockAttribute(): string
    {
        $baseUnitName = $this->baseUnit->name ?? 'casiers';
        $retailUnitName = $this->retailUnit->name ?? 'unités';
        
        $text = "{$this->stock} {$baseUnitName}";
        
        if ($this->retail_stock_remainder > 0) {
            $text .= " + {$this->retail_stock_remainder} {$retailUnitName}";
        }
        
        return $text;
    }

    /**
     * ✅ NOUVEAU : Nom complet avec unité
     * Ex: "Castel Beer 65cl (Casier 24 de 24)"
     */
    public function getDisplayNameAttribute(): string
    {
        if (!$this->baseUnit || !$this->base_unit_quantity) {
            return $this->name;
        }

        return sprintf(
            '%s (%s de %d)',
            $this->name,
            $this->baseUnit->name,
            $this->base_unit_quantity
        );
    }

    /**
     * ✅ NOUVEAU : Prix unitaire de détail
     * Ex: 12,000 FCFA / 24 = 500 FCFA par bouteille
     */
    public function getRetailUnitPriceAttribute(): float
    {
        if (!$this->base_unit_quantity || $this->base_unit_quantity == 0) {
            return $this->unit_price;
        }

        return $this->unit_price / $this->base_unit_quantity;
    }

    /**
     * ✅ NOUVEAU : Coût unitaire de détail
     */
    public function getRetailCostPriceAttribute(): float
    {
        if (!$this->base_unit_quantity || $this->base_unit_quantity == 0) {
            return $this->cost_price;
        }

        return $this->cost_price / $this->base_unit_quantity;
    }

    // ✅ NOUVELLES MÉTHODES pour ajuster le stock

    /**
     * Ajoute du stock (casiers ou bouteilles)
     */
    public function addStock(int $quantity, string $unitType = 'base', string $reason = 'Réapprovisionnement', $userId = null): StockMovement
    {
        $previousStock = $this->stock;
        $previousRemainder = $this->retail_stock_remainder ?? 0;

        if ($unitType === 'base') {
            // Ajout de casiers complets
            $this->stock += $quantity;
        } else {
            // Ajout de bouteilles individuelles
            $baseQty = $this->base_unit_quantity ?? 1;
            $newRemainder = $previousRemainder + $quantity;
            
            // Convertir les bouteilles en casiers si >= base_unit_quantity
            $newCases = intdiv($newRemainder, $baseQty);
            $this->stock += $newCases;
            $this->retail_stock_remainder = $newRemainder % $baseQty;
        }

        $this->save();

        return StockMovement::create([
            'product_id' => $this->id,
            'type' => 'in',
            'quantity' => $quantity,
            'unit_type' => $unitType,
            'previous_stock' => $previousStock,
            'previous_remainder' => $previousRemainder,
            'new_stock' => $this->stock,
            'new_remainder' => $this->retail_stock_remainder,
            'reason' => $reason,
            'user_id' => $userId ?? auth()->id(),
        ]);
    }

    /**
     * Retire du stock (casiers ou bouteilles)
     */
    public function removeStock(int $quantity, string $unitType = 'base', string $reason = 'Sortie', $userId = null): StockMovement
    {
        $previousStock = $this->stock;
        $previousRemainder = $this->retail_stock_remainder ?? 0;

        if ($unitType === 'base') {
            // Retrait de casiers complets
            if ($this->stock < $quantity) {
                throw new \Exception("Stock insuffisant pour {$this->name}. Disponible: {$this->stock} casiers");
            }
            $this->stock -= $quantity;
        } else {
            // Retrait de bouteilles individuelles
            $baseQty = $this->base_unit_quantity ?? 1;
            $totalRetail = ($this->stock * $baseQty) + $previousRemainder;
            
            if ($totalRetail < $quantity) {
                throw new \Exception("Stock insuffisant pour {$this->name}. Disponible: {$totalRetail} unités");
            }
            
            $remainingRetail = $totalRetail - $quantity;
            $this->stock = intdiv($remainingRetail, $baseQty);
            $this->retail_stock_remainder = $remainingRetail % $baseQty;
        }

        $this->save();

        return StockMovement::create([
            'product_id' => $this->id,
            'type' => 'out',
            'quantity' => $quantity,
            'unit_type' => $unitType,
            'previous_stock' => $previousStock,
            'previous_remainder' => $previousRemainder,
            'new_stock' => $this->stock,
            'new_remainder' => $this->retail_stock_remainder,
            'reason' => $reason,
            'user_id' => $userId ?? auth()->id(),
        ]);
    }

    /**
     * Relation avec la catégorie
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Relation avec la sous-catégorie
     */
    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Subcategory::class);
    }

    /**
     * Relation avec les mouvements de stock
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Relation avec les items de vente
     */
    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    /**
     * Relation avec les fournisseurs (Many-to-Many)
     */
    public function suppliers(): BelongsToMany
    {
        return $this->belongsToMany(Supplier::class, 'product_supplier')
            ->withPivot([
                'cost_price',
                'delivery_days',
                'minimum_order_quantity',
                'is_preferred',
                'notes'
            ])
            ->withTimestamps();
    }

    /**
     * Obtenir le fournisseur principal (préféré)
     */
    public function preferredSupplier()
    {
        return $this->suppliers()
            ->wherePivot('is_preferred', true)
            ->first();
    }

    /**
     * Vérifie si le produit est en stock faible
     */
    public function isLowStock(): bool
    {
        return $this->stock <= $this->min_stock && $this->stock > 0;
    }

    /**
     * Vérifie si le produit est en rupture de stock
     */
    public function isOutOfStock(): bool
    {
        return $this->stock == 0;
    }

    /**
     * Scope pour les produits actifs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope pour les produits en stock faible
     */
    public function scopeLowStock($query)
    {
        return $query->whereRaw('stock <= min_stock')->where('stock', '>', 0);
    }

    /**
     * Scope pour les produits en rupture de stock
     */
    public function scopeOutOfStock($query)
    {
        return $query->where('stock', 0);
    }

    /**
     * Relation avec les lignes d'achat
     */
    public function purchaseItems()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    /**
     * Dernier prix d'achat
     */
    public function getLastPurchasePriceAttribute()
    {
        $lastItem = $this->purchaseItems()
            ->join('purchases', 'purchase_items.purchase_id', '=', 'purchases.id')
            ->where('purchases.status', 'received')
            ->orderBy('purchases.received_date', 'desc')
            ->first();
            
        return $lastItem ? $lastItem->unit_cost : null;
    }

    /**
     * Prix moyen d'achat
     */
    public function getAveragePurchasePriceAttribute()
    {
        return $this->purchaseItems()
            ->join('purchases', 'purchase_items.purchase_id', '=', 'purchases.id')
            ->where('purchases.status', 'received')
            ->avg('purchase_items.unit_cost');
    }
}