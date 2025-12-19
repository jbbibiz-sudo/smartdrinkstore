<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'categories';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'color',
        'icon',
        'order',
        'is_active',
        'parent_id'
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    /**
     * The attributes that should be hidden.
     */
    protected $hidden = [
        'deleted_at'
    ];

    /**
     * Boot method for the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Événement avant suppression
        static::deleting(function ($category) {
            // Optionnel : Empêcher la suppression si des produits existent
            if ($category->products()->count() > 0) {
                throw new \Exception('Impossible de supprimer une catégorie contenant des produits');
            }
        });
    }

    /**
     * Relation: Une catégorie a plusieurs produits
     */
    public function products()
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    /**
     * Relation: Une catégorie peut avoir plusieurs sous-catégories
     */
    public function subcategories()
    {
        return $this->hasMany(Subcategory::class, 'category_id');
    }

    /**
     * Relation: Une catégorie peut avoir une catégorie parente
     */
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Relation: Une catégorie peut avoir des catégories enfants
     */
    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * Scope: Seulement les catégories actives
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Ordonner par ordre personnalisé
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order', 'asc');
    }

    /**
     * Scope: Catégories principales (sans parent)
     */
    public function scopeMain($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Accesseur: Obtenir l'URL complète de l'icône
     */
    public function getIconUrlAttribute()
    {
        if ($this->icon && !str_starts_with($this->icon, 'fa-')) {
            return asset('storage/categories/' . $this->icon);
        }
        return null;
    }

    /**
     * Accesseur: Nombre de produits
     */
    public function getProductsCountAttribute()
    {
        return $this->products()->count();
    }

    /**
     * Accesseur: Valeur totale du stock de la catégorie
     */
    public function getTotalStockValueAttribute()
    {
        return $this->products()
            ->selectRaw('SUM(stock * cost_price) as total')
            ->value('total') ?? 0;
    }

    /**
     * Méthode: Vérifier si la catégorie est vide
     */
    public function isEmpty(): bool
    {
        return $this->products()->count() === 0;
    }

    /**
     * Méthode: Obtenir toutes les sous-catégories récursivement
     */
    public function getAllSubcategories()
    {
        $subcategories = collect();
        
        foreach ($this->children as $child) {
            $subcategories->push($child);
            $subcategories = $subcategories->merge($child->getAllSubcategories());
        }
        
        return $subcategories;
    }

    /**
     * Méthode: Activer/Désactiver la catégorie
     */
    public function toggle(): bool
    {
        $this->is_active = !$this->is_active;
        return $this->save();
    }
}