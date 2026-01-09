<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'username',
        'password',
        'phone',
        'address',
        'is_active',
        'email_verified_at',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'is_active' => 'boolean',
        'password' => 'hashed',
    ];

    /**
     * Relation: Un utilisateur peut avoir plusieurs rôles
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_user')
                    ->withTimestamps();
    }

    /**
     * Relation: Les ventes créées par cet utilisateur
     */
    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * Récupérer toutes les permissions de l'utilisateur via ses rôles
     */
    public function getAllPermissions()
    {
        return $this->roles()
            ->with('permissions')
            ->get()
            ->pluck('permissions')
            ->flatten()
            ->unique('id')
            ->values();
    }

    /**
     * Vérifier si l'utilisateur a une permission spécifique
     */
    public function hasPermission($permissionName)
    {
        return $this->getAllPermissions()
            ->contains('name', $permissionName);
    }

    /**
     * Vérifier si l'utilisateur a un rôle spécifique
     */
    public function hasRole($roleName)
    {
        return $this->roles->contains('name', $roleName);
    }

    /**
     * Vérifier si l'utilisateur a un des rôles donnés
     */
    public function hasAnyRole($roles)
    {
        if (is_string($roles)) {
            return $this->roles->contains('name', $roles);
        }

        return $this->roles->whereIn('name', $roles)->isNotEmpty();
    }

    /**
     * Scope: Seulement les utilisateurs actifs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Utilisateurs par rôle
     */
    public function scopeByRole($query, $roleName)
    {
        return $query->whereHas('roles', function ($q) use ($roleName) {
            $q->where('name', $roleName);
        });
    }
}