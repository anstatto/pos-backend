<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Venta extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'numero',
        'cliente_id',
        'user_id',
        'subtotal',
        'impuesto',
        'descuento',
        'total',
        'estado',
        'tipo_venta',
        'fecha_vencimiento',
        'nota',
        'is_activo'
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'impuesto' => 'decimal:2',
        'descuento' => 'decimal:2',
        'total' => 'decimal:2',
        'fecha_vencimiento' => 'date',
        'is_activo' => 'boolean'
    ];

    // Relaciones
    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function detalles()
    {
        return $this->hasMany(VentaDetalle::class);
    }

    public function pagos()
    {
        return $this->morphMany(Pago::class, 'pagable');
    }

    public function cuentaPorCobrar()
    {
        return $this->hasOne(CuentaPorCobrar::class);
    }

    public function comprobanteElectronico()
    {
        return $this->morphOne(ComprobanteElectronico::class, 'comprobanteable');
    }

    // Métodos de utilidad
    public function calcularTotales()
    {
        $this->subtotal = $this->detalles->sum('subtotal');
        $this->impuesto = $this->detalles->sum('impuesto');
        $this->descuento = $this->detalles->sum('descuento');
        $this->total = $this->subtotal + $this->impuesto - $this->descuento;
        $this->save();

        return [
            'subtotal' => $this->subtotal,
            'impuesto' => $this->impuesto,
            'descuento' => $this->descuento,
            'total' => $this->total
        ];
    }

    public function getMontoPagadoAttribute()
    {
        return $this->pagos()->where('is_activo', true)->sum('monto');
    }

    public function getBalancePendienteAttribute()
    {
        return $this->total - $this->getMontoPagadoAttribute();
    }

    public function completar()
    {
        if ($this->tipo_venta === 'CREDITO') {
            $this->cuentaPorCobrar()->create([
                'cliente_id' => $this->cliente_id,
                'monto_original' => $this->total,
                'monto_pendiente' => $this->total,
                'fecha_vencimiento' => $this->fecha_vencimiento,
                'estado' => 'PENDIENTE'
            ]);
        }

        $this->estado = 'COMPLETADA';
        $this->save();

        return $this;
    }

    public function anular()
    {
        $this->estado = 'ANULADA';
        $this->save();

        // Revertir movimientos de inventario
        foreach ($this->detalles as $detalle) {
            $detalle->producto->actualizarStock($detalle->cantidad, 'DEVOLUCION');
        }

        // Anular cuenta por cobrar si existe
        if ($this->cuentaPorCobrar) {
            $this->cuentaPorCobrar->update(['estado' => 'ANULADA']);
        }

        return $this;
    }

    // Método para generar comprobante
    public function generarComprobante(SecuenciaNcf $secuencia)
    {
        if ($this->comprobanteElectronico) {
            throw new \Exception('Esta venta ya tiene un comprobante asociado');
        }

        $ncf = $secuencia->siguienteNcf();

        return $this->comprobanteElectronico()->create([
            'ncf' => $ncf,
            'secuencia_ncf_id' => $secuencia->id,
            'rnc_cedula' => $this->cliente->rnc_cedula,
            'nombre_cliente' => $this->cliente->nombre,
            'monto' => $this->total,
            'itbis' => $this->itbis,
            'fecha_vencimiento' => $secuencia->fecha_vencimiento,
            'estado' => 'ACTIVO'
        ]);
    }
} 