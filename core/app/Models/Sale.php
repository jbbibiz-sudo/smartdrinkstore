<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Sale extends Model
{
    use HasFactory;

    public $timestamps = true;

    protected $fillable = [
        'invoice_number',
        'customer_id',
        'type',
        'payment_method',
        'total_amount',
        'discount_amount',
        'paid_amount',
    ];

    protected $casts = [
        'total_amount'     => 'decimal:2',
        'discount_amount'  => 'decimal:2',
        'paid_amount'      => 'decimal:2',
        'created_at'       => 'datetime:Y-m-d H:i:s',
        'updated_at'       => 'datetime:Y-m-d H:i:s',
    ];

    protected $appends = [
        'balance',
        'is_paid',
    ];

    /* =========================
     | Relations
     ========================= */

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    /* =========================
     | Scopes métier
     ========================= */

    public function scopeBetweenDates($query, $from, $to)
    {
        return $query->whereBetween('created_at', [
            $from . ' 00:00:00',
            $to   . ' 23:59:59',
        ]);
    }

    public function scopePaymentMethod($query, $method)
    {
        return $query->where('payment_method', $method);
    }

    public function scopeSaleType($query, $type)
    {
        return $query->where('type', $type);
    }

    /* =========================
     | Attributs calculés
     ========================= */

    public function getBalanceAttribute()
    {
        return $this->total_amount - $this->paid_amount;
    }

    public function getIsPaidAttribute()
    {
        return $this->balance <= 0;
    }
}
