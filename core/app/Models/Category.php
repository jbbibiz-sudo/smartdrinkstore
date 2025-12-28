<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'categories';

    protected $fillable = [
        'name',
        'code',
        'slug',
        'description',
        'color',
        'icon',
        'position',
        'is_active',
        'parent_id'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'position' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    protected $hidden = [
        'deleted_at'
    ];

    /**
     * Boot method for the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Événement avant création : générer automatiquement code et slug
        static::creating(function ($category) {
            // Générer le code automatiquement s'il est vide
            if (empty($category->code)) {
                $category->code = self::generateUniqueCode($category->name);
            }
            
            // Générer le slug automatiquement s'il est vide
            if (empty($category->slug)) {
                $category->slug = self::generateUniqueSlug($category->name);
            }
        });

        // Événement avant mise à jour : régénérer le slug si le nom change
        static::updating(function ($category) {
            if ($category->isDirty('name') && empty($category->slug)) {
                $category->slug = self::generateUniqueSlug($category->name);
            }
        });

        // Événement avant suppression
        static::deleting(function ($category) {
            // Optionnel : Empêcher la suppression si des produits existent
            if ($category->products()->count() > 0) {
                throw new \Exception('Impossible de supprimer une catégorie contenant des produits');
            }
        });
    }

    /**
     * Générer un code unique pour la catégorie
     */
    private static function generateUniqueCode(string $name): string
    {
        // Nettoyer le nom et créer un code
        $baseCode = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $name), 0, 6));
        
        // Si le code est vide, utiliser un code par défaut
        if (empty($baseCode)) {
            $baseCode = 'CAT';
        }
        
        $code = $baseCode;
        $counter = 1;
        
        // Vérifier l'unicité et ajouter un suffixe si nécessaire
        while (self::where('code', $code)->exists()) {
            $code = $baseCode . $counter;
            $counter++;
        }
        
        return $code;
    }

    /**
     * Générer un slug unique pour la catégorie
     */
    private static function generateUniqueSlug(string $name): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $counter = 1;
        
        // Vérifier l'unicité et ajouter un suffixe si nécessaire
        while (self::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
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
     * Scope: Ordonner par position personnalisée
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('position', 'asc');
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