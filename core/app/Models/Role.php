<?php
// Chemin: C:\smartdrinkstore\core\app\Models\Role.php
// Modèle: Rôle avec permissions

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
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Relation: Les utilisateurs ayant ce rôle
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'role_user')
                    ->withTimestamps();
    }

    /**
     * Relation: Les permissions de ce rôle
     */
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'permission_role')
                    ->withTimestamps();
    }

    /**
     * Vérifie si le rôle a une permission spécifique
     */
    public function hasPermission(string $permissionName): bool
    {
        return $this->permissions()->where('name', $permissionName)->exists();
    }

    /**
     * Assigne une permission au rôle
     */
    public function givePermission(string|Permission $permission): void
    {
        if (is_string($permission)) {
            $permission = Permission::where('name', $permission)->firstOrFail();
        }

        if (!$this->permissions->contains($permission->id)) {
            $this->permissions()->attach($permission->id);
        }
    }

    /**
     * Retire une permission du rôle
     */
    public function revokePermission(string|Permission $permission): void
    {
        if (is_string($permission)) {
            $permission = Permission::where('name', $permission)->firstOrFail();
        }

        $this->permissions()->detach($permission->id);
    }

    /**
     * Scope: Seulement les rôles actifs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
