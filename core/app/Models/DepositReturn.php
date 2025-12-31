<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * ============================================================================
 * MODÈLE: DepositReturn (Retour d'emballage)
 * ============================================================================
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DepositReturn extends Model
{
    use HasFactory;

    protected $fillable = [
        'deposit_id',
        'quantity',
        'good_condition',
        'damaged',
        'lost',
        'damage_penalty',
        'late_penalty',
        'total_penalty',
        'refund_amount',
        'notes',
        'returned_at',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'good_condition' => 'integer',
        'damaged' => 'integer',
        'lost' => 'integer',
        'damage_penalty' => 'decimal:2',
        'late_penalty' => 'decimal:2',
        'total_penalty' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'returned_at' => 'datetime',
    ];

    /**
     * Boot method pour gérer automatiquement les stocks et statuts
     */
    protected static function boot()
    {
        parent::boot();

        // Après création du retour
        static::created(function ($return) {
            $deposit = $return->deposit;
            $depositType = $deposit->depositType;

            // Mettre à jour la quantité en attente
            $deposit->quantity_pending -= $return->quantity;

            // Mettre à jour le statut
            if ($deposit->quantity_pending <= 0) {
                $deposit->status = 'returned';
            } elseif ($deposit->quantity_pending < $deposit->quantity) {
                $deposit->status = 'partial';
            }

            $deposit->save();

            // Mettre à jour le stock d'emballages
            if ($deposit->direction === 'outgoing') {
                // Retour client -> augmente stock (seulement les bons)
                $depositType->increment('current_stock', $return->good_condition);
            } else {
                // Retour fournisseur -> diminue stock
                $depositType->decrement('current_stock', $return->quantity);
            }
        });
    }

    /**
     * Consigne associée
     */
    public function deposit()
    {
        return $this->belongsTo(Deposit::class);
    }
}