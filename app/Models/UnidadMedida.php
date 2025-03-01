<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UnidadMedida extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'unidad_medidas';

    protected $fillable = [
        'nombre',
        'abreviatura',
        'is_activo'
    ];

    protected $casts = [
        'is_activo' => 'boolean'
    ];

    // Relaciones
    public function productos()
    {
        return $this->hasMany(Producto::class);
    }

    public function detalleUnidadProductos()
    {
        return $this->hasMany(DetalleUnidadProducto::class);
    }

    public function compraDetalles()
    {
        return $this->hasMany(CompraDetalle::class);
    }
} 