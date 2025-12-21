<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory;

    protected $fillable = ['name','email','password'];

    public function roles() {
        return $this->belongsToMany(Role::class);
    }

    public function hasRole($roleName) {
        return $this->roles->pluck('name')->contains($roleName);
    }

    public function hasPermission($permissionName) {
        return $this->roles->flatMap->permissions->pluck('name')->contains($permissionName);
    }
}
