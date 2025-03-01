<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cliente extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'condicion_pago_id',
        'tipo_identificacion',
        'identificacion',
        'tipo_contribuyente',
        'direccion',
        'ciudad',
        'provincia',
        'limite_credito',
        'is_activo'
    ];

    protected $casts = [
        'limite_credito' => 'decimal:2',
        'is_activo' => 'boolean'
    ];

    public function condicionPago()
    {
        return $this->belongsTo(CondicionPago::class);
    }

    public function direcciones()
    {
        return $this->hasMany(Direccion::class);
    }

    public function ventas()
    {
        return $this->hasMany(Venta::class);
    }

    public function cuentasPorCobrar()
    {
        return $this->hasMany(CuentaPorCobrar::class);
    }

    public function getBalanceAttribute()
    {
        return $this->cuentasPorCobrar()
            ->where('estado', 'PENDIENTE')
            ->sum('monto_pendiente');
    }

    public function hasExceededCreditLimit()
    {
        return $this->getBalanceAttribute() >= $this->limite_credito;
    }
} 