<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Deposit extends Model
{
    use HasFactory;

    protected $fillable = [
        'deposit_type_id',
        'direction',
        'partner_type',
        'partner_id',
        'quantity',
        'unit_amount',
        'total_amount',
        'quantity_pending',
        'expected_return_at',
        'notes',
        'status',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'quantity_pending' => 'integer',
        'unit_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'expected_return_at' => 'date',
    ];

    /**
     * Boot method pour gérer automatiquement les stocks
     */
    protected static function boot()
    {
        parent::boot();

        // Après création de la consigne
        static::created(function ($deposit) {
            $depositType = $deposit->depositType;

            if ($deposit->direction === 'outgoing') {
                // Sortie client -> diminue stock
                $depositType->decrement('current_stock', $deposit->quantity);
            } else {
                // Entrée fournisseur -> augmente stock
                $depositType->increment('current_stock', $deposit->quantity);
            }
        });
    }

    /**
     * Type d'emballage
     */
    public function depositType()
    {
        return $this->belongsTo(DepositType::class);
    }

    /**
     * Client (si consigne sortante)
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'partner_id')
            ->where('partner_type', 'customer');
    }

    /**
     * Fournisseur (si consigne entrante)
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'partner_id')
            ->where('partner_type', 'supplier');
    }

    /**
     * Retours d'emballages
     */
    public function returns()
    {
        return $this->hasMany(DepositReturn::class);
    }

    /**
     * Scope pour consignes actives
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['active', 'partial']);
    }

    /**
     * Scope pour consignes sortantes
     */
    public function scopeOutgoing($query)
    {
        return $query->where('direction', 'outgoing');
    }

    /**
     * Scope pour consignes entrantes
     */
    public function scopeIncoming($query)
    {
        return $query->where('direction', 'incoming');
    }
}