<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Models\Caja;
use App\Models\ComprobanteElectronico;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Tag(
 *     name="Auditoría",
 *     description="API Endpoints de auditoría y control de cajas"
 * )
 */
class AuditoriaController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/auditoria",
     *     summary="Obtener registros de auditoría",
     *     tags={"Auditoría"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="fecha_inicio",
     *         in="query",
     *         required=false,
     *         description="Fecha inicial para filtrar (YYYY-MM-DD)",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="fecha_fin",
     *         in="query",
     *         required=false,
     *         description="Fecha final para filtrar (YYYY-MM-DD)",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="tipo",
     *         in="query",
     *         required=false,
     *         description="Tipo de auditoría",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="usuario_id",
     *         in="query",
     *         required=false,
     *         description="ID del usuario para filtrar",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de registros de auditoría",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="usuario_id", type="integer"),
     *                     @OA\Property(property="tipo", type="string"),
     *                     @OA\Property(property="tabla", type="string"),
     *                     @OA\Property(property="registro_id", type="integer"),
     *                     @OA\Property(property="valores_anteriores", type="object", nullable=true),
     *                     @OA\Property(property="valores_nuevos", type="object"),
     *                     @OA\Property(property="ip", type="string"),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="usuario", type="object")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación"
     *     )
     * )
     */
    public function index(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'tipo' => 'nullable|string',
            'usuario_id' => 'nullable|exists:users,id'
        ]);

        $query = Auditoria::with('usuario')
            ->orderBy('created_at', 'desc');

        if ($request->fecha_inicio) {
            $query->whereDate('created_at', '>=', $request->fecha_inicio);
        }

        if ($request->fecha_fin) {
            $query->whereDate('created_at', '<=', $request->fecha_fin);
        }

        if ($request->tipo) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->usuario_id) {
            $query->where('usuario_id', $request->usuario_id);
        }

        $auditorias = $query->get();

        return response()->json([
            'status' => 'success',
            'data' => $auditorias
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/auditoria/apertura-caja",
     *     summary="Registrar apertura de caja",
     *     tags={"Auditoría"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"monto_inicial"},
     *             @OA\Property(property="monto_inicial", type="number", format="float", minimum=0),
     *             @OA\Property(property="observacion", type="string", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Caja abierta exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="usuario_id", type="integer"),
     *                 @OA\Property(property="fecha_apertura", type="string", format="date-time"),
     *                 @OA\Property(property="monto_inicial", type="number"),
     *                 @OA\Property(property="monto_final", type="number"),
     *                 @OA\Property(property="estado", type="string", example="ABIERTA"),
     *                 @OA\Property(property="observacion", type="string", nullable=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autorizado"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación o ya existe una caja abierta"
     *     )
     * )
     */
    public function registrarAperturaCaja(Request $request)
    {
        $request->validate([
            'monto_inicial' => 'required|numeric|min:0',
            'observacion' => 'nullable|string'
        ]);

        if (!Auth::check()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Usuario no autenticado'
            ], 401);
        }

        DB::beginTransaction();
        try {
            // Verificar que no haya una caja abierta
            $cajaAbierta = Caja::where('estado', 'ABIERTA')->first();
            if ($cajaAbierta) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Ya existe una caja abierta'
                ], 422);
            }

            // Crear registro de caja
            $caja = Caja::create([
                'usuario_id' => Auth::id(),
                'fecha_apertura' => now(),
                'monto_inicial' => $request->monto_inicial,
                'monto_final' => $request->monto_inicial,
                'estado' => 'ABIERTA',
                'observacion' => $request->observacion
            ]);

            // Registrar auditoría
            Auditoria::create([
                'usuario_id' => Auth::id(),
                'tipo' => 'APERTURA_CAJA',
                'tabla' => 'cajas',
                'registro_id' => $caja->id,
                'valores_anteriores' => null,
                'valores_nuevos' => $caja->toArray(),
                'ip' => $request->ip()
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Caja abierta exitosamente',
                'data' => $caja
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * @OA\Post(
     *     path="/api/auditoria/cierre-caja",
     *     summary="Registrar cierre de caja",
     *     tags={"Auditoría"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"monto_final"},
     *             @OA\Property(property="monto_final", type="number", format="float", minimum=0),
     *             @OA\Property(property="observacion", type="string", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Caja cerrada exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="usuario_id", type="integer"),
     *                 @OA\Property(property="fecha_apertura", type="string", format="date-time"),
     *                 @OA\Property(property="fecha_cierre", type="string", format="date-time"),
     *                 @OA\Property(property="monto_inicial", type="number"),
     *                 @OA\Property(property="monto_final", type="number"),
     *                 @OA\Property(property="estado", type="string", example="CERRADA"),
     *                 @OA\Property(property="observacion", type="string", nullable=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autorizado"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación o no hay caja abierta"
     *     )
     * )
     */
    public function registrarCierreCaja(Request $request)
    {
        $request->validate([
            'monto_final' => 'required|numeric|min:0',
            'observacion' => 'nullable|string'
        ]);

        if (!Auth::check()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Usuario no autenticado'
            ], 401);
        }

        DB::beginTransaction();
        try {
            // Obtener caja abierta
            $caja = Caja::where('estado', 'ABIERTA')->first();
            if (!$caja) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No hay una caja abierta'
                ], 422);
            }

            $valoresAnteriores = $caja->toArray();

            // Actualizar caja
            $caja->update([
                'fecha_cierre' => now(),
                'monto_final' => $request->monto_final,
                'estado' => 'CERRADA',
                'observacion' => $request->observacion
            ]);

            // Registrar auditoría
            Auditoria::create([
                'usuario_id' => Auth::id(),
                'tipo' => 'CIERRE_CAJA',
                'tabla' => 'cajas',
                'registro_id' => $caja->id,
                'valores_anteriores' => $valoresAnteriores,
                'valores_nuevos' => $caja->toArray(),
                'ip' => $request->ip()
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Caja cerrada exitosamente',
                'data' => $caja
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * @OA\Post(
     *     path="/api/auditoria/envio-comprobante",
     *     summary="Registrar envío de comprobante electrónico",
     *     tags={"Auditoría"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"venta_id", "xml", "respuesta_dgii", "estado"},
     *             @OA\Property(property="venta_id", type="integer"),
     *             @OA\Property(property="xml", type="string"),
     *             @OA\Property(property="respuesta_dgii", type="string"),
     *             @OA\Property(property="estado", type="string", enum={"ENVIADO", "ERROR"}),
     *             @OA\Property(property="observacion", type="string", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Comprobante registrado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="venta_id", type="integer"),
     *                 @OA\Property(property="xml", type="string"),
     *                 @OA\Property(property="respuesta_dgii", type="string"),
     *                 @OA\Property(property="estado", type="string"),
     *                 @OA\Property(property="observacion", type="string", nullable=true),
     *                 @OA\Property(property="venta", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autorizado"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación"
     *     )
     * )
     */
    public function registrarEnvioComprobante(Request $request)
    {
        $request->validate([
            'venta_id' => 'required|exists:ventas,id',
            'xml' => 'required|string',
            'respuesta_dgii' => 'required|string',
            'estado' => 'required|in:ENVIADO,ERROR',
            'observacion' => 'nullable|string'
        ]);

        if (!Auth::check()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Usuario no autenticado'
            ], 401);
        }

        DB::beginTransaction();
        try {
            $comprobante = ComprobanteElectronico::create([
                'venta_id' => $request->venta_id,
                'xml' => $request->xml,
                'respuesta_dgii' => $request->respuesta_dgii,
                'estado' => $request->estado,
                'observacion' => $request->observacion
            ]);

            // Registrar auditoría
            Auditoria::create([
                'usuario_id' => Auth::id(),
                'tipo' => 'ENVIO_COMPROBANTE',
                'tabla' => 'comprobantes_electronicos',
                'registro_id' => $comprobante->id,
                'valores_anteriores' => null,
                'valores_nuevos' => $comprobante->toArray(),
                'ip' => $request->ip()
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Comprobante registrado exitosamente',
                'data' => $comprobante->load('venta')
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * @OA\Get(
     *     path="/api/auditoria/estado-caja",
     *     summary="Obtener estado actual de la caja",
     *     tags={"Auditoría"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Estado de caja obtenido exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="usuario_id", type="integer"),
     *                 @OA\Property(property="fecha_apertura", type="string", format="date-time"),
     *                 @OA\Property(property="monto_inicial", type="number"),
     *                 @OA\Property(property="monto_final", type="number"),
     *                 @OA\Property(property="estado", type="string"),
     *                 @OA\Property(property="observacion", type="string", nullable=true),
     *                 @OA\Property(property="movimientos", type="array",
     *                     @OA\Items(type="object")
     *                 ),
     *                 @OA\Property(property="totales", type="object",
     *                     @OA\Property(property="ventas_efectivo", type="number"),
     *                     @OA\Property(property="ventas_otros", type="number"),
     *                     @OA\Property(property="pagos_efectivo", type="number"),
     *                     @OA\Property(property="pagos_otros", type="number")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No hay caja abierta"
     *     )
     * )
     */
    public function obtenerEstadoCaja()
    {
        $caja = Caja::where('estado', 'ABIERTA')
            ->with(['movimientos' => function ($query) {
                $query->whereDate('created_at', now());
            }])
            ->first();

        if (!$caja) {
            return response()->json([
                'status' => 'error',
                'message' => 'No hay una caja abierta'
            ], 404);
        }

        // Calcular totales del día
        $totales = [
            'ventas_efectivo' => $caja->movimientos
                ->where('tipo', 'VENTA')
                ->where('metodo_pago', 'EFECTIVO')
                ->sum('monto'),
            'ventas_otros' => $caja->movimientos
                ->where('tipo', 'VENTA')
                ->where('metodo_pago', '!=', 'EFECTIVO')
                ->sum('monto'),
            'pagos_efectivo' => $caja->movimientos
                ->where('tipo', 'PAGO')
                ->where('metodo_pago', 'EFECTIVO')
                ->sum('monto'),
            'pagos_otros' => $caja->movimientos
                ->where('tipo', 'PAGO')
                ->where('metodo_pago', '!=', 'EFECTIVO')
                ->sum('monto')
        ];

        return response()->json([
            'status' => 'success',
            'data' => [
                'caja' => $caja,
                'totales' => $totales
            ]
        ]);
    }
} 