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
        'type',
        'user_id',              
        'customer_id',
        'supplier_id',
        'deposit_type_id',
        'sale_id',              
        'purchase_id',          
        'quantity',
        'quantity_pending',
        'quantity_returned',
        'unit_deposit_amount',
        'total_deposit_amount',
        'status',
        'issued_at',            
        'expected_return_at',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'quantity_pending' => 'integer',
        'quantity_returned' => 'integer',
        'unit_deposit_amount' => 'decimal:2',
        'total_deposit_amount' => 'decimal:2',
        'issued_at' => 'datetime',
        'expected_return_at' => 'datetime',
    ];

    /**
     * Utilisateur ayant créé la consigne
     */
    public function user()
    {
        return $this->belongsTo(User::class);
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
     * Vente liée (pour consignes sortantes)
     */
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * Achat lié (pour consignes entrantes)
     */
    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
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
}