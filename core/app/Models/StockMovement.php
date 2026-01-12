<?php
// Chemin: app/Models/StockMovement.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    protected $fillable = [
        'product_id',
        'type',
        'quantity',
        'unit_type', // ✅ NOUVEAU : 'base' ou 'retail'
        'previous_stock',
        'previous_remainder', // ✅ NOUVEAU
        'new_stock',
        'new_remainder', // ✅ NOUVEAU
        'reason',
        'reference',
        'user_id',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'previous_stock' => 'integer',
        'previous_remainder' => 'integer',
        'new_stock' => 'integer',
        'new_remainder' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    const TYPE_IN = 'in';
    const TYPE_OUT = 'out';

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Label du type de mouvement
     */
    public function getTypeLabelAttribute()
    {
        return match($this->type) {
            self::TYPE_IN => 'Entrée',
            self::TYPE_OUT => 'Sortie',
            default => 'Inconnu',
        };
    }

    /**
     * Label de l'unité utilisée
     */
    public function getUnitTypeLabelAttribute()
    {
        return match($this->unit_type) {
            'base' => 'Casiers/Packs',
            'retail' => 'Unités individuelles',
            default => 'Non spécifié',
        };
    }

    /**
     * Différence totale en unités de détail
     */
    public function getTotalDifferenceAttribute()
    {
        $product = $this->product;
        $baseQty = $product->base_unit_quantity ?? 1;
        
        $previousTotal = ($this->previous_stock * $baseQty) + $this->previous_remainder;
        $newTotal = ($this->new_stock * $baseQty) + $this->new_remainder;
        
        return $newTotal - $previousTotal;
    }

    public function getTypeColorAttribute()
    {
        return match($this->type) {
            self::TYPE_IN => 'green',
            self::TYPE_OUT => 'red',
            default => 'gray',
        };
    }

    // Scopes
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('created_at', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year);
    }

    public function scopeLastDays($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}