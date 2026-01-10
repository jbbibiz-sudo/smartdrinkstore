<?php
// app/Models/DepositReturn.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DepositReturn extends Model
{
    use HasFactory;

    protected $fillable = [
        'deposit_id',
        'quantity_returned',
        'refund_amount',
        'return_date',
        'notes',
    ];

    protected $casts = [
        'quantity_returned' => 'integer',
        'refund_amount' => 'decimal:2',
        'return_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relation avec la consigne
     */
    public function deposit()
    {
        return $this->belongsTo(Deposit::class);
    }

    /**
     * Obtenir le type de consigne via la relation
     */
    public function getDepositTypeAttribute()
    {
        return $this->deposit?->depositType;
    }

    /**
     * Obtenir le client ou fournisseur selon le type
     */
    public function getEntityAttribute()
    {
        if ($this->deposit->type === Deposit::TYPE_OUTGOING) {
            return $this->deposit->customer;
        }
        return $this->deposit->supplier;
    }

    /**
     * Scope pour les retours d'aujourd'hui
     */
    public function scopeToday($query)
    {
        return $query->whereDate('return_date', today());
    }

    /**
     * Scope pour les retours de cette semaine
     */
    public function scopeThisWeek($query)
    {
        return $query->whereBetween('return_date', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    /**
     * Scope pour les retours de ce mois
     */
    public function scopeThisMonth($query)
    {
        return $query->whereMonth('return_date', now()->month)
            ->whereYear('return_date', now()->year);
    }
}