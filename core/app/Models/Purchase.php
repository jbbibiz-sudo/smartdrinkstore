<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference',
        'supplier_id',
        'user_id',
        'status',
        'subtotal',
        'tax',
        'discount',
        'total_amount',
        'payment_method',
        'mobile_operator',
        'mobile_reference',
        'paid_amount',
        'due_date',
        'credit_days',
        'total_deposit_amount',
        'has_deposits',
        'order_date',
        'expected_delivery_date',
        'received_date',
        'notes',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'discount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'total_deposit_amount' => 'decimal:2',
        'has_deposits' => 'boolean',
        'order_date' => 'date',
        'expected_delivery_date' => 'date',
        'received_date' => 'date',
        'due_date' => 'date',
        'credit_days' => 'integer',
    ];

    /**
     * Boot method pour auto-générer la référence
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($purchase) {
            if (!$purchase->reference) {
                $purchase->reference = 'BON-ACH-' . date('Ymd') . '-' . strtoupper(Str::random(6));
            }
            
            if (!$purchase->order_date) {
                $purchase->order_date = now();
            }
        });

        // Après réception, mettre à jour le stock
        static::updated(function ($purchase) {
            if ($purchase->status === 'received' && $purchase->getOriginal('status') !== 'received') {
                $purchase->updateStock();
            }
        });
    }

    /**
     * Relation avec le fournisseur
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Relation avec l'utilisateur
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation avec les lignes d'achat
     */
    public function items()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    /**
     * Relation avec les consignes créées par cet achat
     */
    public function deposits()
    {
        return $this->hasMany(Deposit::class)->where('type', 'incoming');
    }

    /**
     * Calculer le montant restant à payer
     */
    public function getRemainingAmountAttribute()
    {
        return $this->total_amount - $this->paid_amount;
    }

    /**
     * Vérifier si l'achat est entièrement payé
     */
    public function getIsFullyPaidAttribute()
    {
        return $this->paid_amount >= $this->total_amount;
    }

    /**
     * Vérifier si l'achat est en retard de paiement
     */
    public function getIsOverdueAttribute()
    {
        if ($this->payment_method !== 'credit') {
            return false;
        }

        return $this->due_date && 
               $this->due_date->isPast() && 
               !$this->is_fully_paid;
    }

    /**
     * Mettre à jour le stock après réception
     */
    public function updateStock()
    {
        foreach ($this->items as $item) {
            $product = $item->product;
            
            if ($product) {
                // Ajouter au stock
                $product->addStock(
                    $item->quantity_received,
                    "Achat #{$this->reference}",
                    $this->user_id
                );
            }
        }
    }

    /**
     * Créer les consignes entrantes pour cet achat
     */
    public function createDeposits($userId = null)
    {
        if (!$this->has_deposits) {
            return;
        }

        // ✅ Utiliser le userId passé en paramètre, sinon essayer auth()->id()
        $userId = $userId ?? auth()->id();
        
        if (!$userId) {
            throw new \Exception('User ID requis pour créer des consignes');
        }

        foreach ($this->items as $item) {
            if ($item->is_consigned && $item->deposit_quantity > 0) {
                Deposit::create([
                    'user_id' => $userId, // ✅ CORRECTION ICI
                    'reference' => 'DEP-IN-' . date('Ymd') . '-' . strtoupper(Str::random(6)),
                    'type' => 'incoming',
                    'supplier_id' => $this->supplier_id,
                    'deposit_type_id' => $item->deposit_type_id,
                    'quantity' => $item->deposit_quantity,
                    'quantity_pending' => $item->deposit_quantity,
                    'quantity_returned' => 0,
                    'unit_deposit_amount' => $item->unit_deposit_amount,
                    'total_deposit_amount' => $item->total_deposit_amount,
                    'status' => 'active',
                    'notes' => "Consigne de l'achat #{$this->reference}",
                ]);
            }
        }
    }

    /**
     * Scopes
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopeReceived($query)
    {
        return $query->where('status', 'received');
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', ['confirmed']);
    }

    public function scopeOverdue($query)
    {
        return $query->where('payment_method', 'credit')
                    ->where('due_date', '<', now())
                    ->whereColumn('paid_amount', '<', 'total_amount');
    }
}
