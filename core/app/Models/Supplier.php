<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Supplier extends Model
{
    use HasFactory;

    protected $table = 'suppliers';

    protected $fillable = [
        'name',
        'phone',
        'email',
        'address',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * ✅ CORRIGÉ : Relation Many-to-Many avec les produits
     * Un fournisseur peut fournir plusieurs produits
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_supplier')
            ->withPivot([
                'cost_price',
                'delivery_days',
                'minimum_order_quantity',
                'is_preferred',
                'notes'
            ])
            ->withTimestamps();
    }

    /**
     * Récupère le nombre de produits fournis
     */
    public function getProductsCountAttribute()
    {
        return $this->products()->count();
    }

    /**
     * Scope pour les fournisseurs avec contact
     */
    public function scopeWithContact($query)
    {
        return $query->where(function($q) {
            $q->whereNotNull('phone')
              ->orWhereNotNull('email');
        });
    }

    /**
     * Vérifie si le fournisseur a des informations de contact
     */
    public function getHasContactAttribute()
    {
        return !empty($this->phone) || !empty($this->email);
    }
}