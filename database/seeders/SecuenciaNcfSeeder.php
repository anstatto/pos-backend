<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SecuenciaNcf;

class SecuenciaNcfSeeder extends Seeder
{
    public function run()
    {
        $secuencias = [
            [
                'tipo' => '01-FACTURA',
                'prefijo' => 'B01',
                'secuencia' => 1,
                'secuencia_inicial' => 1,
                'secuencia_final' => 99999999,
                'fecha_vencimiento' => now()->addYear(),
                'is_activo' => true
            ],
            [
                'tipo' => '02-NOTA_CREDITO',
                'prefijo' => 'B04',
                'secuencia' => 1,
                'secuencia_inicial' => 1,
                'secuencia_final' => 99999999,
                'fecha_vencimiento' => now()->addYear(),
                'is_activo' => true
            ],
            [
                'tipo' => '03-NOTA_DEBITO',
                'prefijo' => 'B03',
                'secuencia' => 1,
                'secuencia_inicial' => 1,
                'secuencia_final' => 99999999,
                'fecha_vencimiento' => now()->addYear(),
                'is_activo' => true
            ]
        ];

        foreach ($secuencias as $secuencia) {
            SecuenciaNcf::create($secuencia);
        }
    }
} 