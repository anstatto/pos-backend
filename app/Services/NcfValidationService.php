<?php

namespace App\Services;

class NcfValidationService
{
    private const TIPOS_NCF = [
        'B' => [
            'nombre' => 'Factura de Crédito Fiscal',
            'tipo' => '01-FACTURA',
            'formato' => '/^B[0-9]{2}[0-9]{8}$/'
        ],
        'E' => [
            'nombre' => 'Nota de Crédito',
            'tipo' => '02-NOTA_CREDITO',
            'formato' => '/^E[0-9]{2}[0-9]{8}$/'
        ],
        'P' => [
            'nombre' => 'Nota de Débito',
            'tipo' => '03-NOTA_DEBITO',
            'formato' => '/^P[0-9]{2}[0-9]{8}$/'
        ]
    ];

    public function validar($ncf)
    {
        if (strlen($ncf) !== 11) {
            return [
                'is_valid' => false,
                'message' => 'El NCF debe tener 11 caracteres',
                'tipo' => null
            ];
        }

        $serie = substr($ncf, 0, 1);
        if (!array_key_exists($serie, self::TIPOS_NCF)) {
            return [
                'is_valid' => false,
                'message' => 'Serie de NCF inválida',
                'tipo' => null
            ];
        }

        $tipo = self::TIPOS_NCF[$serie];
        if (!preg_match($tipo['formato'], $ncf)) {
            return [
                'is_valid' => false,
                'message' => 'Formato de NCF inválido para ' . $tipo['nombre'],
                'tipo' => null
            ];
        }

        return [
            'is_valid' => true,
            'message' => 'NCF válido - ' . $tipo['nombre'],
            'tipo' => $tipo['tipo']
        ];
    }

    public function validarSecuencia($ncf, $ultimoNcf = null)
    {
        if (!$ultimoNcf) {
            return true;
        }

        $secuenciaActual = intval(substr($ncf, 3));
        $secuenciaAnterior = intval(substr($ultimoNcf, 3));

        return $secuenciaActual > $secuenciaAnterior;
    }

    public function generarSiguienteNcf($tipo, $ultimoNcf)
    {
        $serie = array_search($tipo, array_column(self::TIPOS_NCF, 'tipo'));
        if (!$serie) {
            throw new \Exception('Tipo de comprobante inválido');
        }

        if (!$ultimoNcf) {
            return $serie . '01' . '00000001';
        }

        $secuencia = intval(substr($ultimoNcf, 3)) + 1;
        return $serie . '01' . str_pad($secuencia, 8, '0', STR_PAD_LEFT);
    }
} 