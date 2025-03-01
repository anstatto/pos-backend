<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Venta;
use App\Models\VentaDetalle;
use App\Models\MovimientoInventario;
use App\Models\CuentaPorCobrar;
use App\Models\SecuenciaNcf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Ventas",
 *     description="API Endpoints de ventas"
 * )
 */
class VentaController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/ventas",
     *     summary="Obtener lista de ventas",
     *     tags={"Ventas"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="cliente_id",
     *         in="query",
     *         description="Filtrar por cliente",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="estado",
     *         in="query",
     *         description="Filtrar por estado",
     *         required=false,
     *         @OA\Schema(type="string", enum={"PENDIENTE", "COMPLETADA", "ANULADA"})
     *     ),
     *     @OA\Parameter(
     *         name="fecha_inicio",
     *         in="query",
     *         description="Fecha inicial para filtrar",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="fecha_fin",
     *         in="query",
     *         description="Fecha final para filtrar",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de ventas",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="numero", type="string"),
     *                     @OA\Property(property="cliente", type="object"),
     *                     @OA\Property(property="total", type="number"),
     *                     @OA\Property(property="estado", type="string"),
     *                     @OA\Property(property="created_at", type="string", format="date-time")
     *                 )
     *             ),
     *             @OA\Property(property="current_page", type="integer"),
     *             @OA\Property(property="total", type="integer")
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $ventas = Venta::with(['cliente', 'detalles.producto'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $ventas
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/ventas",
     *     summary="Crear una nueva venta",
     *     tags={"Ventas"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"cliente_id","tipo_venta","detalles"},
     *             @OA\Property(property="cliente_id", type="integer"),
     *             @OA\Property(property="tipo_venta", type="string", enum={"CONTADO", "CREDITO"}),
     *             @OA\Property(property="fecha_vencimiento", type="string", format="date"),
     *             @OA\Property(property="nota", type="string"),
     *             @OA\Property(property="detalles", type="array",
     *                 @OA\Items(
     *                     required={"producto_id","unidad_medida_id","cantidad","precio"},
     *                     @OA\Property(property="producto_id", type="integer"),
     *                     @OA\Property(property="unidad_medida_id", type="integer"),
     *                     @OA\Property(property="cantidad", type="integer"),
     *                     @OA\Property(property="precio", type="number"),
     *                     @OA\Property(property="descuento", type="number")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Venta creada",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="numero", type="string"),
     *             @OA\Property(property="cliente", type="object"),
     *             @OA\Property(property="total", type="number"),
     *             @OA\Property(property="estado", type="string"),
     *             @OA\Property(property="detalles", type="array",
     *                 @OA\Items(type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Error de validación",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'fecha' => 'required|date',
            'tipo_comprobante' => 'required|in:FACTURA,NOTA_CREDITO,NOTA_DEBITO',
            'detalles' => 'required|array|min:1',
            'detalles.*.producto_id' => 'required|exists:productos,id',
            'detalles.*.cantidad' => 'required|numeric|min:0.01',
            'detalles.*.precio' => 'required|numeric|min:0',
            'detalles.*.descuento' => 'numeric|min:0',
            'observacion' => 'nullable|string'
        ]);

        DB::beginTransaction();
        try {
            // Obtener secuencia NCF
            $secuencia = SecuenciaNcf::where('tipo', $request->tipo_comprobante)
                ->where('is_activo', true)
                ->first();

            if (!$secuencia) {
                throw new \Exception('No hay secuencias NCF disponibles para este tipo de comprobante');
            }

            // Crear venta
            $venta = Venta::create([
                'cliente_id' => $request->cliente_id,
                'fecha' => $request->fecha,
                'tipo_comprobante' => $request->tipo_comprobante,
                'ncf' => $secuencia->prefijo . str_pad($secuencia->secuencia, 8, '0', STR_PAD_LEFT),
                'observacion' => $request->observacion
            ]);

            $total = 0;
            foreach ($request->detalles as $detalle) {
                $producto = \App\Models\Producto::findOrFail($detalle['producto_id']);
                
                // Calcular subtotal
                $subtotal = $detalle['cantidad'] * $detalle['precio'];
                $descuento = $detalle['descuento'] ?? 0;
                $importe = $subtotal - $descuento;
                $total += $importe;

                // Crear detalle de venta
                VentaDetalle::create([
                    'venta_id' => $venta->id,
                    'producto_id' => $detalle['producto_id'],
                    'cantidad' => $detalle['cantidad'],
                    'precio' => $detalle['precio'],
                    'descuento' => $descuento,
                    'subtotal' => $subtotal,
                    'importe' => $importe
                ]);

                // Registrar movimiento de inventario
                MovimientoInventario::create([
                    'producto_id' => $detalle['producto_id'],
                    'tipo' => 'SALIDA',
                    'cantidad' => $detalle['cantidad'],
                    'costo' => $producto->costo,
                    'observacion' => "Venta #{$venta->id}"
                ]);
            }

            // Actualizar total de venta
            $venta->update(['total' => $total]);

            // Incrementar secuencia NCF
            $secuencia->increment('secuencia');

            // Crear cuenta por cobrar si es a crédito
            $cliente = \App\Models\Cliente::findOrFail($request->cliente_id);
            if ($cliente->condicionPago->dias > 0) {
                CuentaPorCobrar::create([
                    'cliente_id' => $cliente->id,
                    'venta_id' => $venta->id,
                    'fecha_vencimiento' => date('Y-m-d', strtotime($request->fecha . " + {$cliente->condicionPago->dias} days")),
                    'monto' => $total,
                    'estado' => 'PENDIENTE'
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Venta registrada exitosamente',
                'data' => $venta->load(['cliente', 'detalles.producto'])
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * @OA\Get(
     *     path="/api/ventas/{id}",
     *     summary="Obtener detalle de una venta",
     *     tags={"Ventas"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detalle de la venta",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="numero", type="string"),
     *             @OA\Property(property="cliente", type="object"),
     *             @OA\Property(property="subtotal", type="number"),
     *             @OA\Property(property="impuesto", type="number"),
     *             @OA\Property(property="descuento", type="number"),
     *             @OA\Property(property="total", type="number"),
     *             @OA\Property(property="estado", type="string"),
     *             @OA\Property(property="detalles", type="array",
     *                 @OA\Items(type="object")
     *             ),
     *             @OA\Property(property="pagos", type="array",
     *                 @OA\Items(type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Venta no encontrada"
     *     )
     * )
     */
    public function show($id)
    {
        return response()->json([
            'status' => 'success',
            'data' => $venta->load([
                'cliente.condicionPago',
                'detalles.producto',
                'cuentaPorCobrar',
                'pagos'
            ])
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/ventas/{id}",
     *     summary="Anular una venta",
     *     tags={"Ventas"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Venta anulada exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Venta no encontrada"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Error al anular la venta",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function destroy($id)
    {
        try {
            $venta = Venta::findOrFail($id);
            
            if ($venta->estado === 'ANULADA') {
                return response()->json(['message' => 'La venta ya está anulada'], 400);
            }

            DB::beginTransaction();

            // Revertir el stock
            foreach ($venta->detalles as $detalle) {
                $detalle->producto->actualizarStock($detalle->cantidad, 'DEVOLUCION');
            }

            // Anular la cuenta por cobrar si existe
            if ($venta->cuentaPorCobrar) {
                $venta->cuentaPorCobrar->update(['estado' => 'ANULADA']);
            }

            $venta->estado = 'ANULADA';
            $venta->save();

            DB::commit();
            return response()->json(['message' => 'Venta anulada exitosamente']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
} 