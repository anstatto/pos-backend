<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Proveedor extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'proveedores';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'rnc',
        'direccion',
        'ciudad',
        'provincia',
        'contacto_nombre',
        'contacto_telefono',
        'balance',
        'is_activo'
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'is_activo' => 'boolean'
    ];

    public function direcciones()
    {
        return $this->hasMany(Direccion::class);
    }

    public function compras()
    {
        return $this->hasMany(Compra::class);
    }

    public function cuentasPorPagar()
    {
        return $this->hasMany(CuentaPorPagar::class);
    }

    public function actualizarBalance()
    {
        $this->balance = $this->cuentasPorPagar()
            ->where('estado', 'PENDIENTE')
            ->sum('monto_pendiente');
        $this->save();

        return $this->balance;
    }
} 