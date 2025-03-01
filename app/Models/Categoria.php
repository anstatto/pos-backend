<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Categoria extends Model
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

    public function productos()
    {
        return $this->hasMany(Producto::class);
    }
} 