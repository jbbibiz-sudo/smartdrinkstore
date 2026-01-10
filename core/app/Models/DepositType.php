<?php
// app/Models/DepositType.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DepositType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'deposit_amount',
    ];

    protected $casts = [
        'deposit_amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Consignes utilisant ce type
     */
    public function deposits()
    {
        return $this->hasMany(Deposit::class);
    }

    /**
     * Consignes actives
     */
    public function activeDeposits()
    {
        return $this->deposits()->whereIn('status', ['active', 'partial']);
    }

    /**
     * Lignes d'achat utilisant ce type d'emballage
     */
    public function purchaseItems()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    /**
     * Calculer le stock actuel d'emballages
     */
    public function getQuantityInStockAttribute()
    {
        // Stock = Consignes entrantes (reçues) - Consignes sortantes (données aux clients)
        $incoming = $this->deposits()
            ->where('type', 'incoming')
            ->where('status', 'active')
            ->sum('quantity');

        $outgoing = $this->deposits()
            ->where('type', 'outgoing')
            ->whereIn('status', ['active', 'partial'])
            ->sum('quantity_pending');

        return $incoming - $outgoing;
    }

    /**
     * Vérifier s'il y a assez d'emballages disponibles
     */
    public function hasEnoughStock(int $requiredQuantity): bool
    {
        return $this->quantity_in_stock >= $requiredQuantity;
    }

    /**
     * Scope pour les types actifs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}