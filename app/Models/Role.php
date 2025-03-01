<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'descripcion',
        'is_activo'
    ];

    protected $casts = [
        'is_activo' => 'boolean'
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_role');
    }

    public function permisos()
    {
        return $this->belongsToMany(Permiso::class, 'role_permiso');
    }

    public function hasPermission($permission)
    {
        return $this->permisos()->where('name', $permission)->exists();
    }

    public function givePermissionTo($permission)
    {
        if (is_string($permission)) {
            $permission = Permiso::where('name', $permission)->firstOrFail();
        }
        return $this->permisos()->syncWithoutDetaching($permission);
    }

    public function revokePermissionTo($permission)
    {
        if (is_string($permission)) {
            $permission = Permiso::where('name', $permission)->firstOrFail();
        }
        return $this->permisos()->detach($permission);
    }
} 