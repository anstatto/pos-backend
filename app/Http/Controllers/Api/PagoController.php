<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pago;
use App\Models\CuentaPorCobrar;
use App\Models\CuentaPorPagar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(
 *     name="Pagos",
 *     description="API Endpoints de pagos de cuentas por cobrar y pagar"
 * )
 */
class PagoController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/pagos",
     *     summary="Obtener lista de pagos",
     *     tags={"Pagos"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Lista de pagos obtenida exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="cuenta_por_cobrar_id", type="integer", nullable=true),
     *                     @OA\Property(property="cuenta_por_pagar_id", type="integer", nullable=true),
     *                     @OA\Property(property="fecha", type="string", format="date"),
     *                     @OA\Property(property="monto", type="number"),
     *                     @OA\Property(property="metodo_pago", type="string"),
     *                     @OA\Property(property="referencia", type="string", nullable=true),
     *                     @OA\Property(property="observacion", type="string", nullable=true),
     *                     @OA\Property(property="estado", type="string"),
     *                     @OA\Property(property="cuentaPorCobrar", type="object", nullable=true),
     *                     @OA\Property(property="cuentaPorPagar", type="object", nullable=true)
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function index()
    {
        $pagos = Pago::with(['cuentaPorCobrar.cliente', 'cuentaPorPagar.proveedor'])
            ->orderBy('fecha', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $pagos
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/pagos/cobro",
     *     summary="Registrar pago de cuenta por cobrar",
     *     tags={"Pagos"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"cuenta_por_cobrar_id", "fecha", "monto", "metodo_pago"},
     *             @OA\Property(property="cuenta_por_cobrar_id", type="integer"),
     *             @OA\Property(property="fecha", type="string", format="date"),
     *             @OA\Property(property="monto", type="number", format="float", minimum=0.01),
     *             @OA\Property(property="metodo_pago", type="string", enum={"EFECTIVO", "CHEQUE", "TRANSFERENCIA"}),
     *             @OA\Property(property="referencia", type="string", maxLength=50, nullable=true),
     *             @OA\Property(property="observacion", type="string", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Pago registrado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="cuenta_por_cobrar_id", type="integer"),
     *                 @OA\Property(property="fecha", type="string", format="date"),
     *                 @OA\Property(property="monto", type="number"),
     *                 @OA\Property(property="metodo_pago", type="string"),
     *                 @OA\Property(property="referencia", type="string", nullable=true),
     *                 @OA\Property(property="observacion", type="string", nullable=true),
     *                 @OA\Property(property="estado", type="string"),
     *                 @OA\Property(property="cuentaPorCobrar", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación o cuenta no válida para pago"
     *     )
     * )
     */
    public function registrarPagoCobro(Request $request)
    {
        $request->validate([
            'cuenta_por_cobrar_id' => 'required|exists:cuentas_por_cobrar,id',
            'fecha' => 'required|date',
            'monto' => 'required|numeric|min:0.01',
            'metodo_pago' => 'required|in:EFECTIVO,CHEQUE,TRANSFERENCIA',
            'referencia' => 'nullable|string|max:50',
            'observacion' => 'nullable|string'
        ]);

        DB::beginTransaction();
        try {
            $cuentaPorCobrar = CuentaPorCobrar::findOrFail($request->cuenta_por_cobrar_id);

            // Verificar que la cuenta esté pendiente
            if ($cuentaPorCobrar->estado !== 'PENDIENTE') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'La cuenta por cobrar no está pendiente de pago'
                ], 422);
            }

            // Verificar que el monto no exceda el saldo pendiente
            $saldoPendiente = $cuentaPorCobrar->monto - $cuentaPorCobrar->pagos()->sum('monto');
            if ($request->monto > $saldoPendiente) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'El monto del pago excede el saldo pendiente'
                ], 422);
            }

            // Registrar pago
            $pago = Pago::create([
                'cuenta_por_cobrar_id' => $request->cuenta_por_cobrar_id,
                'fecha' => $request->fecha,
                'monto' => $request->monto,
                'metodo_pago' => $request->metodo_pago,
                'referencia' => $request->referencia,
                'observacion' => $request->observacion
            ]);

            // Actualizar estado de la cuenta si se completó el pago
            if ($request->monto >= $saldoPendiente) {
                $cuentaPorCobrar->update(['estado' => 'PAGADA']);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Pago registrado exitosamente',
                'data' => $pago->load('cuentaPorCobrar.cliente')
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * @OA\Post(
     *     path="/api/pagos/pagar",
     *     summary="Registrar pago de cuenta por pagar",
     *     tags={"Pagos"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"cuenta_por_pagar_id", "fecha", "monto", "metodo_pago"},
     *             @OA\Property(property="cuenta_por_pagar_id", type="integer"),
     *             @OA\Property(property="fecha", type="string", format="date"),
     *             @OA\Property(property="monto", type="number", format="float", minimum=0.01),
     *             @OA\Property(property="metodo_pago", type="string", enum={"EFECTIVO", "CHEQUE", "TRANSFERENCIA"}),
     *             @OA\Property(property="referencia", type="string", maxLength=50, nullable=true),
     *             @OA\Property(property="observacion", type="string", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Pago registrado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="cuenta_por_pagar_id", type="integer"),
     *                 @OA\Property(property="fecha", type="string", format="date"),
     *                 @OA\Property(property="monto", type="number"),
     *                 @OA\Property(property="metodo_pago", type="string"),
     *                 @OA\Property(property="referencia", type="string", nullable=true),
     *                 @OA\Property(property="observacion", type="string", nullable=true),
     *                 @OA\Property(property="estado", type="string"),
     *                 @OA\Property(property="cuentaPorPagar", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación o cuenta no válida para pago"
     *     )
     * )
     */
    public function registrarPagoPagar(Request $request)
    {
        $request->validate([
            'cuenta_por_pagar_id' => 'required|exists:cuentas_por_pagar,id',
            'fecha' => 'required|date',
            'monto' => 'required|numeric|min:0.01',
            'metodo_pago' => 'required|in:EFECTIVO,CHEQUE,TRANSFERENCIA',
            'referencia' => 'nullable|string|max:50',
            'observacion' => 'nullable|string'
        ]);

        DB::beginTransaction();
        try {
            $cuentaPorPagar = CuentaPorPagar::findOrFail($request->cuenta_por_pagar_id);

            // Verificar que la cuenta esté pendiente
            if ($cuentaPorPagar->estado !== 'PENDIENTE') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'La cuenta por pagar no está pendiente de pago'
                ], 422);
            }

            // Verificar que el monto no exceda el saldo pendiente
            $saldoPendiente = $cuentaPorPagar->monto - $cuentaPorPagar->pagos()->sum('monto');
            if ($request->monto > $saldoPendiente) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'El monto del pago excede el saldo pendiente'
                ], 422);
            }

            // Registrar pago
            $pago = Pago::create([
                'cuenta_por_pagar_id' => $request->cuenta_por_pagar_id,
                'fecha' => $request->fecha,
                'monto' => $request->monto,
                'metodo_pago' => $request->metodo_pago,
                'referencia' => $request->referencia,
                'observacion' => $request->observacion
            ]);

            // Actualizar estado de la cuenta si se completó el pago
            if ($request->monto >= $saldoPendiente) {
                $cuentaPorPagar->update(['estado' => 'PAGADA']);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Pago registrado exitosamente',
                'data' => $pago->load('cuentaPorPagar.proveedor')
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * @OA\Get(
     *     path="/api/pagos/{pago}",
     *     summary="Obtener detalles de un pago",
     *     tags={"Pagos"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="pago",
     *         in="path",
     *         required=true,
     *         description="ID del pago",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detalles del pago obtenidos exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="cuenta_por_cobrar_id", type="integer", nullable=true),
     *                 @OA\Property(property="cuenta_por_pagar_id", type="integer", nullable=true),
     *                 @OA\Property(property="fecha", type="string", format="date"),
     *                 @OA\Property(property="monto", type="number"),
     *                 @OA\Property(property="metodo_pago", type="string"),
     *                 @OA\Property(property="referencia", type="string", nullable=true),
     *                 @OA\Property(property="observacion", type="string", nullable=true),
     *                 @OA\Property(property="estado", type="string"),
     *                 @OA\Property(property="cuentaPorCobrar", type="object", nullable=true),
     *                 @OA\Property(property="cuentaPorPagar", type="object", nullable=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Pago no encontrado"
     *     )
     * )
     */
    public function show(Pago $pago)
    {
        return response()->json([
            'status' => 'success',
            'data' => $pago->load([
                'cuentaPorCobrar.cliente',
                'cuentaPorCobrar.venta',
                'cuentaPorPagar.proveedor',
                'cuentaPorPagar.compra'
            ])
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/pagos/{pago}/anular",
     *     summary="Anular un pago",
     *     tags={"Pagos"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="pago",
     *         in="path",
     *         required=true,
     *         description="ID del pago",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Pago anulado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="estado", type="string", example="ANULADO"),
     *                 @OA\Property(property="cuentaPorCobrar", type="object", nullable=true),
     *                 @OA\Property(property="cuentaPorPagar", type="object", nullable=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Pago no encontrado"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="El pago ya está anulado"
     *     )
     * )
     */
    public function anular(Pago $pago)
    {
        if ($pago->estado === 'ANULADO') {
            return response()->json([
                'status' => 'error',
                'message' => 'El pago ya está anulado'
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Actualizar estado del pago
            $pago->update(['estado' => 'ANULADO']);

            // Actualizar estado de la cuenta por cobrar o pagar
            if ($pago->cuenta_por_cobrar_id) {
                $pago->cuentaPorCobrar->update(['estado' => 'PENDIENTE']);
            } else {
                $pago->cuentaPorPagar->update(['estado' => 'PENDIENTE']);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Pago anulado exitosamente',
                'data' => $pago->load(['cuentaPorCobrar.cliente', 'cuentaPorPagar.proveedor'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
} 