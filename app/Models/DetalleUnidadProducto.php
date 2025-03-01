<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DetalleUnidadProducto extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'detalle_unidad_productos';

    protected $fillable = [
        'producto_id',
        'unidad_medida_id',
        'precio_venta',
        'precio_compra',
        'contenido',
        'is_activo'
    ];

    protected $casts = [
        'precio_venta' => 'decimal:2',
        'precio_compra' => 'decimal:2',
        'contenido' => 'integer',
        'is_activo' => 'boolean'
    ];

    // Relaciones
    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function unidadMedida()
    {
        return $this->belongsTo(UnidadMedida::class);
    }

    // Métodos de utilidad
    public function getPrecioUnitarioVentaAttribute()
    {
        return $this->precio_venta / $this->contenido;
    }

    public function getPrecioUnitarioCompraAttribute()
    {
        return $this->precio_compra / $this->contenido;
    }
} 