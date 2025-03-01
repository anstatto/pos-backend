<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MovimientoInventario extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'movimiento_inventarios';

    protected $fillable = [
        'producto_id',
        'tipo',
        'cantidad',
        'stock_anterior',
        'stock_actual',
        'ajustable_type',
        'ajustable_id',
        'nota',
        'is_activo'
    ];

    protected $casts = [
        'cantidad' => 'decimal:2',
        'stock_anterior' => 'decimal:2',
        'stock_actual' => 'decimal:2',
        'is_activo' => 'boolean'
    ];

    // Relaciones
    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function ajustable()
    {
        return $this->morphTo();
    }

    // Scopes
    public function scopePorTipo($query, $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    public function scopePorFecha($query, $fecha)
    {
        return $query->whereDate('created_at', $fecha);
    }

    public function scopePorRango($query, $inicio, $fin)
    {
        return $query->whereBetween('created_at', [$inicio, $fin]);
    }

    // Eventos
    protected static function booted()
    {
        static::created(function ($movimiento) {
            $movimiento->producto->actualizarStock($movimiento->cantidad, $movimiento->tipo);
        });
    }
} 