<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'address',
        'balance',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
    ];

    /* =========================
     | Relations
     ========================= */

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    /* =========================
     | Scopes utiles
     ========================= */

    public function scopeWithBalance($query)
    {
        return $query->where('balance', '>', 0);
    }
}
