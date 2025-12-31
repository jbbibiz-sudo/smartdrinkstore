<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * ============================================================================
 * MODÈLE: DepositType (Type d'emballage consignable)
 * ============================================================================
 */
class DepositType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'category',
        'amount',
        'initial_stock',
        'current_stock',
        'description',
        'is_active',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'initial_stock' => 'integer',
        'current_stock' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Consignes utilisant ce type
     */
    public function deposits()
    {
        return $this->hasMany(Deposit::class);
    }

    /**
     * Consignes actives
     */
    public function activeDeposits()
    {
        return $this->hasMany(Deposit::class)
            ->whereIn('status', ['active', 'partial']);
    }
}