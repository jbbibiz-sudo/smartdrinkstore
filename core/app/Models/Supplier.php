<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function scopeWithContact($query)
    {
        return $query->where(function($q) {
            $q->whereNotNull('phone')
              ->orWhereNotNull('email');
        });
    }

    public function getHasContactAttribute()
    {
        return !empty($this->phone) || !empty($this->email);
    }
}