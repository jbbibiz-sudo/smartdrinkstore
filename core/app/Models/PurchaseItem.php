<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_id',
        'product_id',
        'quantity',
        'quantity_received',
        'unit_cost',
        'subtotal',
        'is_consigned',
        'deposit_type_id',
        'deposit_quantity',
        'unit_deposit_amount',
        'total_deposit_amount',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'quantity_received' => 'integer',
        'unit_cost' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'is_consigned' => 'boolean',
        'deposit_quantity' => 'integer',
        'unit_deposit_amount' => 'decimal:2',
        'total_deposit_amount' => 'decimal:2',
    ];

    /**
     * Boot method pour calculer automatiquement le subtotal
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            // Calculer le sous-total
            $item->subtotal = $item->quantity * $item->unit_cost;
            
            // Calculer le montant total des consignes
            if ($item->is_consigned && $item->deposit_quantity > 0) {
                $item->total_deposit_amount = $item->deposit_quantity * $item->unit_deposit_amount;
            } else {
                $item->total_deposit_amount = 0;
            }
        });
    }

    /**
     * Relation avec l'achat
     */
    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    /**
     * Relation avec le produit
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Relation avec le type d'emballage consigné
     */
    public function depositType()
    {
        return $this->belongsTo(DepositType::class);
    }

    /**
     * Calculer la quantité restante à recevoir
     */
    public function getRemainingQuantityAttribute()
    {
        return $this->quantity - $this->quantity_received;
    }

    /**
     * Vérifier si la ligne est entièrement reçue
     */
    public function getIsFullyReceivedAttribute()
    {
        return $this->quantity_received >= $this->quantity;
    }
}
