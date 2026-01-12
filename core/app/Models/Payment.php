<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    /**
     * Table associée au modèle
     */
    protected $table = 'payments';

    /**
     * Les attributs qui peuvent être assignés en masse
     */
    protected $fillable = [
        'customer_id',
        'amount',
        'payment_method',
        'reference',
        'notes',
        'payment_date',
    ];

    /**
     * Les attributs qui doivent être castés
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relation: Un paiement appartient à un client
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Scope: Paiements d'aujourd'hui
     */
    public function scopeToday($query)
    {
        return $query->whereDate('payment_date', today());
    }

    /**
     * Scope: Paiements de cette semaine
     */
    public function scopeThisWeek($query)
    {
        return $query->whereBetween('payment_date', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    /**
     * Scope: Paiements de ce mois
     */
    public function scopeThisMonth($query)
    {
        return $query->whereMonth('payment_date', now()->month)
                    ->whereYear('payment_date', now()->year);
    }

    /**
     * Scope: Paiements par méthode
     */
    public function scopeByMethod($query, $method)
    {
        return $query->where('payment_method', $method);
    }

    /**
     * Accessor: Formater le montant
     */
    public function getFormattedAmountAttribute()
    {
        return number_format($this->amount, 0, ',', ' ') . ' FCFA';
    }

    /**
     * Accessor: Obtenir le label de la méthode de paiement
     */
    public function getPaymentMethodLabelAttribute()
    {
        $labels = [
            'cash' => 'Espèces',
            'mobile_money' => 'Mobile Money',
            'bank_transfer' => 'Virement bancaire',
            'check' => 'Chèque',
        ];

        return $labels[$this->payment_method] ?? $this->payment_method;
    }
}
