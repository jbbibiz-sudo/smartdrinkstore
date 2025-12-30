<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CreditPayment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sale_id',
        'amount',
        'payment_method',
        'payment_date',
        'notes',
        'recorded_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    /**
     * Relation avec la vente
     */
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * Relation avec l'utilisateur qui a enregistré le paiement
     */
    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * Scope pour les paiements d'une période
     */
    public function scopeInPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('payment_date', [$startDate, $endDate]);
    }

    /**
     * Scope pour les paiements du mois en cours
     */
    public function scopeThisMonth($query)
    {
        return $query->whereMonth('payment_date', now()->month)
                    ->whereYear('payment_date', now()->year);
    }

    /**
     * Scope pour les paiements d'aujourd'hui
     */
    public function scopeToday($query)
    {
        return $query->whereDate('payment_date', today());
    }
}