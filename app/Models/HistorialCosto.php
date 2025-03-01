<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistorialCosto extends Model
{
    use HasFactory;

    protected $table = 'historial_costos';

    protected $fillable = [
        'producto_id',
        'precio_costo'
    ];

    protected $casts = [
        'precio_costo' => 'decimal:2',
        'fecha' => 'datetime'
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }
} 