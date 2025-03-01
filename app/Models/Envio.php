<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Envio extends Model
{
    use HasFactory;

    protected $fillable = [
        'venta_id',
        'estado',
        'fecha_envio',
        'fecha_entrega',
        'direccion'
    ];

    protected $casts = [
        'fecha_envio' => 'datetime',
        'fecha_entrega' => 'datetime'
    ];

    public function venta()
    {
        return $this->belongsTo(Venta::class);
    }
} 