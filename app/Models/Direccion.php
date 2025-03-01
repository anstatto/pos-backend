<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Direccion extends Model
{
    use HasFactory;

    protected $table = 'direcciones';

    protected $fillable = [
        'calle',
        'ciudad',
        'estado',
        'pais',
        'codigo_postal',
        'cliente_id',
        'proveedor_id',
        'is_activo'
    ];

    protected $casts = [
        'is_activo' => 'boolean'
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function getDireccionCompletaAttribute()
    {
        return "{$this->calle}, {$this->ciudad}, {$this->estado}, {$this->pais} {$this->codigo_postal}";
    }
} 