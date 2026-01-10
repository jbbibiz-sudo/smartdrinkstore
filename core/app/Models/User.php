<?php
// app/Models/User.php

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
     * Relation: Les achats créés par cet utilisateur
     */
    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    /**
     * Relation: Les mouvements de stock créés par cet utilisateur
     */
    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Relation: Les consignes créées par cet utilisateur
     */
    public function deposits()
    {
        return $this->hasMany(Deposit::class);
    }

    /**
     * ✅ MÉTHODES DE GESTION DES RÔLES
     */

    /**
     * Assigner un rôle à l'utilisateur
     */
    public function assignRole($role)
    {
        if (is_string($role)) {
            $role = Role::where('name', $role)->firstOrFail();
        }

        if (!$this->roles->contains($role->id)) {
            $this->roles()->attach($role->id);
        }

        return $this;
    }

    /**
     * Assigner plusieurs rôles
     */
    public function assignRoles($roles)
    {
        foreach ($roles as $role) {
            $this->assignRole($role);
        }

        return $this;
    }

    /**
     * Retirer un rôle
     */
    public function removeRole($role)
    {
        if (is_string($role)) {
            $role = Role::where('name', $role)->firstOrFail();
        }

        $this->roles()->detach($role->id);

        return $this;
    }

    /**
     * Synchroniser les rôles
     */
    public function syncRoles($roles)
    {
        $roleIds = collect($roles)->map(function ($role) {
            if (is_string($role)) {
                return Role::where('name', $role)->firstOrFail()->id;
            }
            return is_object($role) ? $role->id : $role;
        })->toArray();

        $this->roles()->sync($roleIds);

        return $this;
    }

    /**
     * ✅ MÉTHODES DE VÉRIFICATION
     */

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
     * ✅ CORRIGÉ : Renommé de can() en hasPermissionTo() pour éviter le conflit
     * Alias pour hasPermission
     */
    public function hasPermissionTo($permissionName)
    {
        return $this->hasPermission($permissionName);
    }

    /**
     * Vérifier si l'utilisateur a un rôle spécifique
     */
    public function hasRole($roleName)
    {
        if (is_array($roleName)) {
            return $this->hasAnyRole($roleName);
        }

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
     * Vérifier si l'utilisateur a TOUS les rôles donnés
     */
    public function hasAllRoles($roles)
    {
        if (is_string($roles)) {
            $roles = [$roles];
        }

        foreach ($roles as $role) {
            if (!$this->hasRole($role)) {
                return false;
            }
        }

        return true;
    }

    /**
     * ✅ MÉTHODES UTILITAIRES
     */

    /**
     * Vérifier si l'utilisateur est un admin
     */
    public function isAdmin()
    {
        return $this->hasRole('admin');
    }

    /**
     * Vérifier si l'utilisateur est un manager
     */
    public function isManager()
    {
        return $this->hasRole('manager');
    }

    /**
     * Vérifier si l'utilisateur est un vendeur/caissier
     */
    public function isCashier()
    {
        return $this->hasRole('cashier');
    }

    /**
     * Vérifier si l'utilisateur est gestionnaire de stock
     */
    public function isInventoryManager()
    {
        return $this->hasRole('inventory_manager');
    }

    /**
     * ✅ SCOPES
     */

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

    /**
     * Scope: Utilisateurs avec une permission spécifique
     */
    public function scopeWithPermission($query, $permissionName)
    {
        return $query->whereHas('roles.permissions', function ($q) use ($permissionName) {
            $q->where('name', $permissionName);
        });
    }

    /**
     * ✅ ACCESSORS
     */

    /**
     * Accesseur: Obtenir les noms des rôles
     */
    public function getRoleNamesAttribute()
    {
        return $this->roles->pluck('name')->toArray();
    }

    /**
     * Accesseur: Obtenir les noms des permissions
     */
    public function getPermissionNamesAttribute()
    {
        return $this->getAllPermissions()->pluck('name')->toArray();
    }
}