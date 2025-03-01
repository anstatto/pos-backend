<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Compra extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'numero',
        'proveedor_id',
        'user_id',
        'subtotal',
        'impuesto',
        'descuento',
        'total',
        'estado',
        'tipo_compra',
        'fecha_vencimiento',
        'factura_proveedor',
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
    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function detalles()
    {
        return $this->hasMany(CompraDetalle::class);
    }

    public function pagos()
    {
        return $this->morphMany(Pago::class, 'pagable');
    }

    public function cuentaPorPagar()
    {
        return $this->hasOne(CuentaPorPagar::class);
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
        if ($this->tipo_compra === 'CREDITO') {
            $this->cuentaPorPagar()->create([
                'proveedor_id' => $this->proveedor_id,
                'monto_original' => $this->total,
                'monto_pendiente' => $this->total,
                'fecha_vencimiento' => $this->fecha_vencimiento,
                'estado' => 'PENDIENTE'
            ]);
        }

        // Actualizar costos de productos
        foreach ($this->detalles as $detalle) {
            $detalle->producto->actualizarPrecioCompra($detalle->precio);
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
            $detalle->producto->actualizarStock($detalle->cantidad, 'SALIDA');
        }

        // Anular cuenta por pagar si existe
        if ($this->cuentaPorPagar) {
            $this->cuentaPorPagar->update(['estado' => 'ANULADA']);
        }

        return $this;
    }

    // Método para registrar comprobante
    public function registrarComprobante($ncf, $rnc)
    {
        if ($this->comprobanteElectronico) {
            throw new \Exception('Esta compra ya tiene un comprobante asociado');
        }

        return $this->comprobanteElectronico()->create([
            'ncf' => $ncf,
            'rnc_cedula' => $rnc,
            'nombre_cliente' => $this->proveedor->nombre,
            'monto' => $this->total,
            'itbis' => $this->itbis,
            'fecha_vencimiento' => now()->addYear(),
            'estado' => 'ACTIVO'
        ]);
    }
} 