<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pago extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'numero',
        'pagable_type',
        'pagable_id',
        'monto',
        'tipo_pago',
        'referencia',
        'user_id',
        'nota',
        'is_activo'
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'is_activo' => 'boolean'
    ];

    // Relaciones
    public function pagable()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Eventos
    protected static function booted()
    {
        static::created(function ($pago) {
            if ($pago->pagable instanceof Venta) {
                if ($pago->pagable->cuentaPorCobrar) {
                    $cuenta = $pago->pagable->cuentaPorCobrar;
                    $cuenta->monto_pendiente -= $pago->monto;
                    
                    if ($cuenta->monto_pendiente <= 0) {
                        $cuenta->estado = 'PAGADA';
                    }
                    
                    $cuenta->save();
                }
            } elseif ($pago->pagable instanceof Compra) {
                if ($pago->pagable->cuentaPorPagar) {
                    $cuenta = $pago->pagable->cuentaPorPagar;
                    $cuenta->monto_pendiente -= $pago->monto;
                    
                    if ($cuenta->monto_pendiente <= 0) {
                        $cuenta->estado = 'PAGADA';
                    }
                    
                    $cuenta->save();
                }
            }
        });
    }
} 