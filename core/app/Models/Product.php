<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'sku',
        'category_id',
        'subcategory_id',
        'supplier_id',
        'unit_price',
        'cost_price',
        'stock',
        'min_stock',
        'is_consigned',
        'consignment_value',
        'is_active'
    ];

    protected $casts = [
        'is_consigned' => 'boolean',
        'is_active' => 'boolean',
        'unit_price' => 'float',
        'cost_price' => 'float',
        'consignment_value' => 'float',
        'stock' => 'integer',
        'min_stock' => 'integer'
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function subcategory()
    {
        return $this->belongsTo(Subcategory::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopeActive(Builder $query)
    {
        return $query->where('is_active', true);
    }

    public function scopeLowStock(Builder $query)
    {
        return $query->whereColumn('stock', '<=', 'min_stock');
    }

    public function scopeSearch(Builder $query, $term)
    {
        return $query->where(function (Builder $q) use ($term) {
            $q->where('name', 'like', "%$term%")
              ->orWhere('sku', 'like', "%$term%");
        });
    }

    // 🔹 Helpers
    public function getIsLowStockAttribute()
    {
        return $this->stock <= $this->min_stock;
    }

    public function addStock(int $qty, ?string $reason = null, ?string $expiry = null)
    {
        $previous = $this->stock;
        $this->stock += $qty;
        $this->save();

        $this->stockMovements()->create([
            'type'=>'in',
            'quantity'=>$qty,
            'previous_stock'=>$previous,
            'new_stock'=>$this->stock,
            'reason'=>$reason ?? 'Ajout stock',
            'expiry_date'=>$expiry
        ]);
    }

    public function removeStock(int $qty, ?string $reason = null, ?string $expiry = null)
    {
        if($qty > $this->stock) throw new \Exception('Stock insuffisant');
        $previous = $this->stock;
        $this->stock -= $qty;
        $this->save();

        $this->stockMovements()->create([
            'type'=>'out',
            'quantity'=>$qty,
            'previous_stock'=>$previous,
            'new_stock'=>$this->stock,
            'reason'=>$reason ?? 'Retrait stock',
            'expiry_date'=>$expiry
        ]);
    }

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }
}