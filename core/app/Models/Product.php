<?php
// app/Models/Product.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
        'is_active'
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
    ];

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
     * ✅ NOUVEAU : Relation avec les fournisseurs (Many-to-Many)
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
     * ✅ NOUVEAU : Obtenir le fournisseur principal
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
     * Ajoute du stock et crée un mouvement
     */
    public function addStock(int $quantity, string $reason = 'Réapprovisionnement', $userId = null): void
    {
        $previousStock = $this->stock;
        $this->stock += $quantity;
        $this->save();

        StockMovement::create([
            'product_id' => $this->id,
            'type' => 'in',
            'quantity' => $quantity,
            'previous_stock' => $previousStock,
            'new_stock' => $this->stock,
            'reason' => $reason,
            'user_id' => $userId ?? auth()->id(),
        ]);
    }

    /**
     * Retire du stock et crée un mouvement
     */
    public function removeStock(int $quantity, string $reason = 'Sortie', $userId = null): void
    {
        if ($this->stock < $quantity) {
            throw new \Exception("Stock insuffisant pour {$this->name}");
        }

        $previousStock = $this->stock;
        $this->stock -= $quantity;
        $this->save();

        StockMovement::create([
            'product_id' => $this->id,
            'type' => 'out',
            'quantity' => $quantity,
            'previous_stock' => $previousStock,
            'new_stock' => $this->stock,
            'reason' => $reason,
            'user_id' => $userId ?? auth()->id(),
        ]);
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
}