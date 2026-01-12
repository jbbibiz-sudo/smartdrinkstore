<?php

// Chemin: app/Models/ProductUnit.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductUnit extends Model
{
    protected $fillable = [
        'code',
        'name',
        'symbol',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Produits utilisant cette unité comme base
     */
    public function productsAsBase()
    {
        return $this->hasMany(Product::class, 'base_unit_id');
    }

    /**
     * Produits utilisant cette unité comme détail
     */
    public function productsAsRetail()
    {
        return $this->hasMany(Product::class, 'retail_unit_id');
    }

    /**
     * Scope pour les unités actives
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}