<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    use HasFactory;

    /**
     * Champs remplissables
     */
    protected $fillable = [
        'sale_id',
        'product_id',
        'quantity',
        'unit_price',
        'subtotal',
    ];

    /**
     * Casts
     */
    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    /**
     * Relation: Un item appartient à une vente
     */
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * Relation: Un item concerne un produit
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
