<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
//use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Subcategory extends Model
{
   // use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'code',
        'description',
        'category_id',
        'color',
        'is_active',
        'position'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'position' => 'integer'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($subcategory) {
            if (empty($subcategory->slug)) {
                $subcategory->slug = Str::slug($subcategory->name);
            }
            if (empty($subcategory->code)) {
                // Générer un code unique
                $baseCode = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $subcategory->name), 0, 3));
                $counter = 1;
                $code = $subcategory->category->code . '-' . $baseCode;
                
                while (static::where('code', $code)->exists()) {
                    $code = $subcategory->category->code . '-' . $baseCode . $counter;
                    $counter++;
                }
                
                $subcategory->code = $code;
            }
        });
    }

    /**
     * Relation avec la catégorie parente
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Relation avec les produits
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Scope pour les sous-catégories actives
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Nombre de produits actifs
     */
    public function activeProductsCount()
    {
        return $this->products()->where('is_active', true)->count();
    }

    /**
     * Valeur totale du stock des produits actifs
     */
    public function totalStockValue()
    {
        return $this->products()
            ->where('is_active', true)
            ->get()
            ->sum(function ($product) {
                return $product->stock * $product->unit_price;
            });
    }

    /**
     * Accessor pour le nom complet (catégorie > sous-catégorie)
     */
    public function getFullNameAttribute()
    {
        return $this->category->name . ' > ' . $this->name;
    }

    /**
     * Accessor pour le chemin hiérarchique
     */
    public function getHierarchicalPathAttribute()
    {
        return [
            'category_id' => $this->category_id,
            'category_name' => $this->category->name,
            'subcategory_id' => $this->id,
            'subcategory_name' => $this->name,
        ];
    }
}