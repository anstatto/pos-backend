<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VentaDetalle extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'venta_detalles';

    protected $fillable = [
        'venta_id',
        'producto_id',
        'unidad_medida_id',
        'cantidad',
        'precio',
        'descuento',
        'impuesto',
        'subtotal',
        'total',
        'is_activo'
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'precio' => 'decimal:2',
        'descuento' => 'decimal:2',
        'impuesto' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'total' => 'decimal:2',
        'is_activo' => 'boolean'
    ];

    // Relaciones
    public function venta()
    {
        return $this->belongsTo(Venta::class);
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function unidadMedida()
    {
        return $this->belongsTo(UnidadMedida::class);
    }

    // Eventos
    protected static function booted()
    {
        static::created(function ($detalle) {
            $detalle->producto->actualizarStock($detalle->cantidad, 'SALIDA');
        });

        static::creating(function ($detalle) {
            $detalle->calcularTotales();
        });
    }

    // Métodos de utilidad
    public function calcularTotales()
    {
        $this->subtotal = $this->cantidad * $this->precio;
        $this->impuesto = $this->subtotal * $this->producto->getItbisAttribute();
        $this->total = $this->subtotal + $this->impuesto - $this->descuento;

        return $this;
    }
} 