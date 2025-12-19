<?php

/**
 * =============================================================================
 * ÉTAPE 2 : Créer le modèle StockMovement
 * =============================================================================
 * Fichier : app/Models/StockMovement.php
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    protected $fillable = [
        'product_id',
        'type',
        'quantity',
        'previous_stock',
        'new_stock',
        'reason',
        'reference',
        'user_id',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'previous_stock' => 'integer',
        'new_stock' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Types de mouvements disponibles
     */
    const TYPE_IN = 'in';
    const TYPE_OUT = 'out';
    const TYPE_ADJUSTMENT = 'adjustment';
    const TYPE_RETURN = 'return';
    const TYPE_CONSIGNMENT_RETURN = 'consignment_return';

    /**
     * Relations
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Accessors
     */
    public function getTypeLabelAttribute()
    {
        return match($this->type) {
            self::TYPE_IN => 'Entrée',
            self::TYPE_OUT => 'Sortie',
            self::TYPE_ADJUSTMENT => 'Ajustement',
            self::TYPE_RETURN => 'Retour',
            default => 'Inconnu',
        };
    }

    public function getDifferenceAttribute()
    {
        return $this->new_stock - $this->previous_stock;
    }

    public function getTypeColorAttribute()
    {
        return match($this->type) {
            self::TYPE_IN => 'green',
            self::TYPE_OUT => 'red',
            self::TYPE_ADJUSTMENT => 'orange',
            self::TYPE_RETURN => 'blue',
            default => 'gray',
        };
    }

    /**
     * Scopes
     */
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