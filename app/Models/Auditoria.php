<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Auditoria extends Model
{
    use HasFactory;

    protected $table = 'auditorias';

    protected $fillable = [
        'auditable_type',
        'auditable_id',
        'evento',
        'valores_antiguos',
        'valores_nuevos',
        'user_id',
        'url',
        'ip_address',
        'user_agent'
    ];

    protected $casts = [
        'valores_antiguos' => 'json',
        'valores_nuevos' => 'json'
    ];

    // Relaciones
    public function auditable()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Métodos de utilidad
    public static function registrar($modelo, $evento, $valoresAntiguos = null, $valoresNuevos = null)
    {
        return static::create([
            'auditable_type' => get_class($modelo),
            'auditable_id' => $modelo->id,
            'evento' => $evento,
            'valores_antiguos' => $valoresAntiguos,
            'valores_nuevos' => $valoresNuevos,
            'user_id' => auth()->id(),
            'url' => request()->fullUrl(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);
    }
} 