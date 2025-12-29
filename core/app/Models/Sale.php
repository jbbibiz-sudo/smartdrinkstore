<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;

    /**
     * ✅ FILLABLE CORRIGÉ - Tous les champs de la table
     */
    protected $fillable = [
        'invoice_number',
        'customer_id',
        'user_id',
        'type',
        'payment_method',
        'total_amount',
        'discount',
        'paid_amount',
    ];

    /**
     * ✅ CASTS pour convertir les types
     */
    protected $casts = [
        'total_amount' => 'decimal:2',
        'discount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relation: Une vente appartient à un client
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Relation: Une vente a plusieurs items
     */
    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    /**
     * Accessor: Montant restant à payer
     */
    public function getUnpaidAmountAttribute()
    {
        return $this->total_amount - $this->paid_amount;
    }

    /**
     * Accessor: Statut de paiement
     */
    public function getPaymentStatusAttribute()
    {
        if ($this->paid_amount >= $this->total_amount) {
            return 'paid';
        } elseif ($this->paid_amount > 0) {
            return 'partial';
        } else {
            return 'unpaid';
        }
    }

    /**
     * Scope: Ventes d'aujourd'hui
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Scope: Ventes de cette semaine
     */
    public function scopeThisWeek($query)
    {
        return $query->whereBetween('created_at', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    /**
     * Scope: Ventes de ce mois
     */
    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year);
    }

    /**
     * Scope: Ventes à crédit
     */
    public function scopeCredit($query)
    {
        return $query->where('payment_method', 'credit');
    }

    /**
     * Scope: Ventes impayées
     */
    public function scopeUnpaid($query)
    {
        return $query->whereColumn('paid_amount', '<', 'total_amount');
    }
}
