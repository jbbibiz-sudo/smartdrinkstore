<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Purchase;

class Deposit extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference',
        'type',  // ✅ CORRIGÉ: était 'direction'
        'customer_id',  // ✅ AJOUTÉ
        'supplier_id',  // ✅ AJOUTÉ
        'deposit_type_id',
        'quantity',
        'quantity_pending',
        'quantity_returned',  // ✅ AJOUTÉ
        'unit_deposit_amount',  // ✅ AJOUTÉ
        'total_deposit_amount',  // ✅ AJOUTÉ
        'status',
        'notes',
        'expected_return_at',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'quantity_pending' => 'integer',
        'quantity_returned' => 'integer',
        'unit_deposit_amount' => 'decimal:2',
        'total_deposit_amount' => 'decimal:2',
        'expected_return_at' => 'date',
    ];

    // ✅ CORRIGÉ: Boot method retiré car le controller gère déjà le stock
    // Cela évite le double décrément et les erreurs SQL

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
        return $this->belongsTo(Customer::class);
    }

    /**
     * Fournisseur (si consigne entrante)
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
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
        return $query->whereIn('status', ['active', 'partially_returned']);
    }

    /**
     * Scope pour consignes sortantes
     */
    public function scopeOutgoing($query)
    {
        return $query->where('type', 'outgoing');
    }

    /**
     * Scope pour consignes entrantes
     */
    public function scopeIncoming($query)
    {
        return $query->where('type', 'incoming');
    }

    /**
     * Relation avec l'achat (pour consignes entrantes)
     */
    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }
}
