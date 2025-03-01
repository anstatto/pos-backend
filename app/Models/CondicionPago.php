<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CondicionPago extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'condicion_pagos';

    protected $fillable = [
        'descripcion',
        'dias',
        'is_activo'
    ];

    protected $casts = [
        'is_activo' => 'boolean',
        'dias' => 'integer'
    ];

    public function clientes()
    {
        return $this->hasMany(Cliente::class);
    }
} 