<?php
// app/Models/Deposit.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Deposit extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'reference',
        'type',
        'sale_id',
        'customer_id',
        'supplier_id',
        'deposit_type_id',
        'quantity',
        'quantity_pending',
        'quantity_returned',
        'unit_deposit_amount',
        'total_deposit_amount',
        'status',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'quantity_pending' => 'integer',
        'quantity_returned' => 'integer',
        'unit_deposit_amount' => 'decimal:2',
        'total_deposit_amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Types de consignes
     */
    const TYPE_INCOMING = 'incoming'; // Consignes entrantes (achat)
    const TYPE_OUTGOING = 'outgoing'; // Consignes sortantes (vente)

    /**
     * Statuts des consignes
     */
    const STATUS_ACTIVE = 'active';       // Consigne active
    const STATUS_PARTIAL = 'partial';     // Partiellement retournée
    const STATUS_RETURNED = 'returned';   // Totalement retournée
    const STATUS_CANCELLED = 'cancelled'; // Annulée

    /**
     * Boot method pour auto-générer la référence
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($deposit) {
            if (!$deposit->reference) {
                $prefix = $deposit->type === self::TYPE_INCOMING ? 'DEP-IN' : 'DEP-OUT';
                $deposit->reference = $prefix . '-' . date('Ymd') . '-' . strtoupper(Str::random(6));
            }

            // Initialiser quantity_pending si non défini
            if (is_null($deposit->quantity_pending)) {
                $deposit->quantity_pending = $deposit->quantity;
            }

            // Initialiser quantity_returned si non défini
            if (is_null($deposit->quantity_returned)) {
                $deposit->quantity_returned = 0;
            }
        });
    }

    /**
     * Relation avec l'utilisateur qui a créé la consigne
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation avec le type de consigne (emballage)
     */
    public function depositType()
    {
        return $this->belongsTo(DepositType::class);
    }

    /**
     * Relation avec la vente (consignes sortantes)
     */
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * Relation avec le client (consignes sortantes)
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Relation avec le fournisseur (consignes entrantes)
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Relation avec les retours de consignes
     */
    public function returns()
    {
        return $this->hasMany(DepositReturn::class);
    }

    /**
     * Calculer la quantité restante
     */
    public function getRemainingQuantityAttribute()
    {
        return $this->quantity - $this->quantity_returned;
    }

    /**
     * Vérifier si la consigne est complète
     */
    public function getIsFullyReturnedAttribute()
    {
        return $this->quantity_returned >= $this->quantity;
    }

    /**
     * Calculer le montant restant
     */
    public function getRemainingAmountAttribute()
    {
        return $this->remaining_quantity * $this->unit_deposit_amount;
    }

    /**
     * Enregistrer un retour partiel ou complet
     */
    public function recordReturn(int $quantityReturned, ?string $notes = null)
    {
        if ($quantityReturned > $this->quantity_pending) {
            throw new \Exception("La quantité retournée ne peut pas dépasser la quantité en attente");
        }

        $this->quantity_returned += $quantityReturned;
        $this->quantity_pending -= $quantityReturned;

        // Mettre à jour le statut
        if ($this->quantity_pending === 0) {
            $this->status = self::STATUS_RETURNED;
        } elseif ($this->quantity_returned > 0) {
            $this->status = self::STATUS_PARTIAL;
        }

        $this->save();

        // Créer l'enregistrement de retour
        return DepositReturn::create([
            'deposit_id' => $this->id,
            'quantity_returned' => $quantityReturned,
            'refund_amount' => $quantityReturned * $this->unit_deposit_amount,
            'return_date' => now(),
            'notes' => $notes,
        ]);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeIncoming($query)
    {
        return $query->where('type', self::TYPE_INCOMING);
    }

    public function scopeOutgoing($query)
    {
        return $query->where('type', self::TYPE_OUTGOING);
    }

    public function scopePending($query)
    {
        return $query->where('quantity_pending', '>', 0);
    }

    /**
     * Accessors pour les labels
     */
    public function getTypeLabelAttribute()
    {
        return match($this->type) {
            self::TYPE_INCOMING => 'Entrante',
            self::TYPE_OUTGOING => 'Sortante',
            default => 'Inconnu',
        };
    }

    public function getStatusLabelAttribute()
    {
        return match($this->status) {
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_PARTIAL => 'Partielle',
            self::STATUS_RETURNED => 'Retournée',
            self::STATUS_CANCELLED => 'Annulée',
            default => 'Inconnu',
        };
    }

    public function getStatusColorAttribute()
    {
        return match($this->status) {
            self::STATUS_ACTIVE => 'blue',
            self::STATUS_PARTIAL => 'orange',
            self::STATUS_RETURNED => 'green',
            self::STATUS_CANCELLED => 'red',
            default => 'gray',
        };
    }
}