<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Caja extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'numero',
        'user_id',
        'monto_inicial',
        'monto_final',
        'diferencia',
        'estado',
        'fecha_apertura',
        'fecha_cierre',
        'nota',
        'is_activo'
    ];

    protected $casts = [
        'monto_inicial' => 'decimal:2',
        'monto_final' => 'decimal:2',
        'diferencia' => 'decimal:2',
        'fecha_apertura' => 'datetime',
        'fecha_cierre' => 'datetime',
        'is_activo' => 'boolean'
    ];

    // Relaciones
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Métodos de utilidad
    public function cerrar($montoFinal, $nota = null)
    {
        $this->monto_final = $montoFinal;
        $this->diferencia = $montoFinal - $this->monto_inicial;
        $this->fecha_cierre = now();
        $this->estado = 'CERRADA';
        $this->nota = $nota;
        $this->save();

        return $this;
    }

    public function estaAbierta()
    {
        return $this->estado === 'ABIERTA';
    }

    public function estaCerrada()
    {
        return $this->estado === 'CERRADA';
    }
} 