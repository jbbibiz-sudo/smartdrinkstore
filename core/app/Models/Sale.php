<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'customer_id',
        'user_id',
        'type',
        'payment_method',
        'total_amount',
        'discount',
        'paid_amount',
        'deposit_amount',
        'due_date',
        'credit_days',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'discount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_date' => 'date',
        'credit_days' => 'integer',
    ];

    /**
     * Relation avec le client
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Relation avec le vendeur
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation avec les lignes de vente
     */
    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }


    /*  
    * Deposits
    */
    public function deposits()
    {
        return $this->hasMany(Deposit::class);
    }

    /**
     * Relation avec les paiements de crédit
     */
    public function creditPayments()
    {
        return $this->hasMany(CreditPayment::class);
    }

    /**
     * Calculer le reste à payer
     */
    public function getRemainingAmountAttribute()
    {
        return $this->total_amount - $this->paid_amount;
    }

    /**
     * Vérifier si la vente est en retard
     */
    public function getIsOverdueAttribute()
    {
        if ($this->payment_method !== 'credit') {
            return false;
        }

        return $this->due_date && 
               $this->due_date->isPast() && 
               $this->paid_amount < $this->total_amount;
    }

    /**
     * Vérifier si la vente est complètement payée
     */
    public function getIsFullyPaidAttribute()
    {
        return $this->paid_amount >= $this->total_amount;
    }
}
