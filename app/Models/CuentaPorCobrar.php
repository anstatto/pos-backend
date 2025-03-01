<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CuentaPorCobrar extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'cuentas_por_cobrar';

    protected $fillable = [
        'venta_id',
        'cliente_id',
        'monto_original',
        'monto_pendiente',
        'fecha_vencimiento',
        'estado',
        'is_activo'
    ];

    protected $casts = [
        'monto_original' => 'decimal:2',
        'monto_pendiente' => 'decimal:2',
        'fecha_vencimiento' => 'date',
        'is_activo' => 'boolean'
    ];

    // Relaciones
    public function venta()
    {
        return $this->belongsTo(Venta::class);
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    // Eventos
    protected static function booted()
    {
        static::saving(function ($cuenta) {
            if ($cuenta->fecha_vencimiento < now() && $cuenta->estado === 'PENDIENTE') {
                $cuenta->estado = 'VENCIDA';
            }
        });
    }

    // Métodos de utilidad
    public function registrarPago($monto)
    {
        $this->monto_pendiente -= $monto;
        
        if ($this->monto_pendiente <= 0) {
            $this->estado = 'PAGADA';
        }
        
        $this->save();
        return $this;
    }

    public function anular()
    {
        $this->estado = 'ANULADA';
        $this->save();
        return $this;
    }
} 