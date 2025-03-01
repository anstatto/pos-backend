<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AjusteInventarioDetalle extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'ajuste_inventario_id',
        'producto_id',
        'unidad_medida_id',
        'cantidad',
        'costo',
        'nota',
        'is_activo'
    ];

    protected $casts = [
        'cantidad' => 'decimal:2',
        'costo' => 'decimal:2',
        'is_activo' => 'boolean'
    ];

    // Relaciones
    public function ajusteInventario()
    {
        return $this->belongsTo(AjusteInventario::class);
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function unidadMedida()
    {
        return $this->belongsTo(UnidadMedida::class);
    }

    // Métodos de utilidad
    public function getSubtotalAttribute()
    {
        return $this->cantidad * $this->costo;
    }
} 