<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ComprobanteElectronico extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'comprobante_electronicos';

    protected $fillable = [
        'ncf',
        'secuencia_ncf_id',
        'rnc_cedula',
        'nombre_cliente',
        'monto',
        'itbis',
        'estado',
        'fecha_vencimiento',
        'is_activo'
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'itbis' => 'decimal:2',
        'fecha_vencimiento' => 'date',
        'is_activo' => 'boolean'
    ];

    // Relaciones
    public function secuenciaNcf()
    {
        return $this->belongsTo(SecuenciaNcf::class);
    }

    public function comprobanteable()
    {
        return $this->morphTo();
    }

    // Métodos
    public function anular()
    {
        $this->update(['estado' => 'ANULADO']);
    }

    public function esAnulado()
    {
        return $this->estado === 'ANULADO';
    }

    public function estaVencido()
    {
        return $this->fecha_vencimiento < now();
    }

    // Métodos de utilidad
    public static function generarComprobante($documento, $secuenciaNcf)
    {
        $ncf = $secuenciaNcf->generarNcf();

        return static::create([
            'ncf' => $ncf,
            'comprobanteable_type' => get_class($documento),
            'comprobanteable_id' => $documento->id,
            'secuencia_ncf_id' => $secuenciaNcf->id,
            'rnc_cedula' => $documento->cliente->identificacion ?? $documento->proveedor->rnc,
            'nombre_cliente' => $documento->cliente->name ?? $documento->proveedor->name,
            'monto' => $documento->total,
            'itbis' => $documento->impuesto,
            'estado' => 'ACTIVO',
            'fecha_vencimiento' => $secuenciaNcf->fecha_vencimiento
        ]);
    }
} 