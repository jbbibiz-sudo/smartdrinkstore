<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $table = 'customers';

    protected $fillable = [
        'name',
        'phone',
        'email',
        'address',
        'balance',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function scopeWithBalance($query)
    {
        return $query->where('balance', '>', 0);
    }

    public function getFormattedBalanceAttribute()
    {
        return number_format($this->balance, 0, ',', ' ') . ' FCFA';
    }

    public function setBalanceAttribute($value)
    {
        $this->attributes['balance'] = max(0, $value);
    }
    
    /**
     * Consignes du client (sortantes)
     */
    public function deposits()
    {
        return $this->hasMany(Deposit::class)->where('type', 'outgoing');
    }

    /**
     * Consignes actives du client
     */
    public function activeDeposits()
    {
        return $this->deposits()->where('status', 'active');
    }

    /**
     * Montant total des consignes en cours
     */
    public function getTotalActiveDepositsAttribute()
    {
        return $this->activeDeposits()->sum('total_deposit_amount');
    }
}