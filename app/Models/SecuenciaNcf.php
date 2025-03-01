<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SecuenciaNcf extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'secuencias_ncf';

    protected $fillable = [
        'tipo',
        'prefijo',
        'secuencia',
        'secuencia_inicial',
        'secuencia_final',
        'fecha_vencimiento',
        'is_activo'
    ];

    protected $casts = [
        'secuencia' => 'integer',
        'secuencia_inicial' => 'integer',
        'secuencia_final' => 'integer',
        'fecha_vencimiento' => 'date',
        'is_activo' => 'boolean'
    ];

    // Relaciones
    public function comprobantes()
    {
        return $this->hasMany(ComprobanteElectronico::class);
    }

    // Métodos de utilidad
    public function siguienteNcf()
    {
        if (!$this->is_activo) {
            throw new \Exception('Esta secuencia de NCF está inactiva');
        }

        if ($this->secuencia >= $this->secuencia_final) {
            throw new \Exception('Se ha alcanzado el límite de la secuencia NCF');
        }

        if ($this->fecha_vencimiento && now()->gt($this->fecha_vencimiento)) {
            throw new \Exception('La secuencia NCF ha vencido');
        }

        $ncf = $this->prefijo . str_pad($this->secuencia, 8, '0', STR_PAD_LEFT);
        $this->increment('secuencia');

        return $ncf;
    }

    public function estaVencida()
    {
        return $this->fecha_vencimiento < now();
    }

    public function estaAgotada()
    {
        return $this->secuencia >= $this->secuencia_final;
    }
} 