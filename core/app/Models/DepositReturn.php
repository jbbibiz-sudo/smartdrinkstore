<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DepositReturn extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference',
        'deposit_id',
        'user_id', 
        'quantity_returned',
        'quantity_good_condition',
        'quantity_damaged',
        'quantity_lost',
        'refund_amount',
        'damage_penalty',
        'delay_penalty',
        'total_penalty',
        'net_refund',
        'notes',
        'returned_at',
    ];

    protected $casts = [
        'quantity_returned' => 'integer',
        'quantity_good_condition' => 'integer',
        'quantity_damaged' => 'integer',
        'quantity_lost' => 'integer',
        'damage_penalty' => 'decimal:2',
        'delay_penalty' => 'decimal:2',
        'total_penalty' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'net_refund' => 'decimal:2',
        'returned_at' => 'datetime',
    ];

    // ✅ CORRIGÉ: Boot method retiré car le controller gère déjà
    // la mise à jour du statut et du stock
    // Cela évite les problèmes de double mise à jour

    /**
     * Consigne associée
     */
    public function deposit()
    {
        return $this->belongsTo(Deposit::class);
    }
}
