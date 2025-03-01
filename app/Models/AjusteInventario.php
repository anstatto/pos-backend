<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AjusteInventario extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'numero',
        'user_id',
        'tipo',
        'motivo',
        'nota',
        'estado',
        'is_activo'
    ];

    protected $casts = [
        'is_activo' => 'boolean'
    ];

    // Relaciones
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function detalles()
    {
        return $this->hasMany(AjusteInventarioDetalle::class);
    }

    // Métodos
    public function completar()
    {
        if ($this->estado !== 'PENDIENTE') {
            throw new \Exception('Este ajuste no puede ser completado porque no está pendiente');
        }

        foreach ($this->detalles as $detalle) {
            $detalle->producto->actualizarStock(
                $detalle->cantidad,
                $this->tipo === 'ENTRADA' ? 'AJUSTE_ENTRADA' : 'AJUSTE_SALIDA',
                [
                    'ajustable_type' => self::class,
                    'ajustable_id' => $this->id,
                    'nota' => $this->motivo
                ]
            );
        }

        $this->estado = 'COMPLETADO';
        $this->save();

        return $this;
    }

    public function anular()
    {
        if ($this->estado === 'ANULADO') {
            throw new \Exception('Este ajuste ya está anulado');
        }

        if ($this->estado === 'COMPLETADO') {
            foreach ($this->detalles as $detalle) {
                $detalle->producto->actualizarStock(
                    $detalle->cantidad,
                    $this->tipo === 'ENTRADA' ? 'AJUSTE_SALIDA' : 'AJUSTE_ENTRADA',
                    [
                        'ajustable_type' => self::class,
                        'ajustable_id' => $this->id,
                        'nota' => 'Anulación de ajuste: ' . $this->motivo
                    ]
                );
            }
        }

        $this->estado = 'ANULADO';
        $this->save();

        return $this;
    }
} 