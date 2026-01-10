<?php
// app/Models/Role.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'display_name',
        'description',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Relation: Un rôle peut être assigné à plusieurs utilisateurs
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'role_user')
                    ->withTimestamps();
    }

    /**
     * Relation: Un rôle peut avoir plusieurs permissions
     */
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'permission_role')
                    ->withTimestamps();
    }

    /**
     * ✅ NOUVEAU : Assigner une permission au rôle
     */
    public function givePermissionTo($permission)
    {
        if (is_string($permission)) {
            $permission = Permission::where('name', $permission)->firstOrFail();
        }

        if (!$this->permissions->contains($permission->id)) {
            $this->permissions()->attach($permission->id);
        }

        return $this;
    }

    /**
     * ✅ NOUVEAU : Assigner plusieurs permissions
     */
    public function givePermissionsTo($permissions)
    {
        foreach ($permissions as $permission) {
            $this->givePermissionTo($permission);
        }

        return $this;
    }

    /**
     * ✅ NOUVEAU : Retirer une permission
     */
    public function revokePermissionTo($permission)
    {
        if (is_string($permission)) {
            $permission = Permission::where('name', $permission)->firstOrFail();
        }

        $this->permissions()->detach($permission->id);

        return $this;
    }

    /**
     * ✅ NOUVEAU : Synchroniser les permissions
     */
    public function syncPermissions($permissions)
    {
        $permissionIds = collect($permissions)->map(function ($permission) {
            if (is_string($permission)) {
                return Permission::where('name', $permission)->firstOrFail()->id;
            }
            return is_object($permission) ? $permission->id : $permission;
        })->toArray();

        $this->permissions()->sync($permissionIds);

        return $this;
    }

    /**
     * ✅ NOUVEAU : Vérifier si le rôle a une permission
     */
    public function hasPermission($permissionName)
    {
        return $this->permissions->contains('name', $permissionName);
    }

    /**
     * ✅ NOUVEAU : Scope pour les rôles actifs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}