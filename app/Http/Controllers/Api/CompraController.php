<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Compra;
use App\Models\CompraDetalle;
use App\Models\MovimientoInventario;
use App\Models\CuentaPorPagar;
use App\Models\HistorialCosto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Compras",
 *     description="API Endpoints de compras y gestión de comprobantes fiscales"
 * )
 */
class CompraController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/compras",
     *     summary="Obtener lista de compras",
     *     tags={"Compras"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="proveedor_id",
     *         in="query",
     *         description="Filtrar por proveedor",
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
     *         description="Lista de compras",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="numero", type="string"),
     *                     @OA\Property(property="proveedor", type="object"),
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
        $compras = Compra::with(['proveedor', 'detalles.producto'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $compras
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/compras",
     *     summary="Crear una nueva compra",
     *     tags={"Compras"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"proveedor_id","tipo_compra","detalles"},
     *             @OA\Property(property="proveedor_id", type="integer"),
     *             @OA\Property(property="tipo_compra", type="string", enum={"CONTADO", "CREDITO"}),
     *             @OA\Property(property="fecha_vencimiento", type="string", format="date"),
     *             @OA\Property(property="factura_proveedor", type="string"),
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
     *         description="Compra creada",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="numero", type="string"),
     *             @OA\Property(property="proveedor", type="object"),
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
            'proveedor_id' => 'required|exists:proveedores,id',
            'fecha' => 'required|date',
            'tipo_comprobante' => 'required|in:01-FACTURA,02-NOTA_CREDITO,03-NOTA_DEBITO',
            'ncf' => [
                'required',
                'string',
                'regex:/^[BEP][0-9]{2}[0-9]{8}$/', // Validación de formato NCF
                Rule::unique('compras')->where(function ($query) use ($request) {
                    return $query->where('proveedor_id', $request->proveedor_id);
                })
            ],
            'detalles' => 'required|array|min:1',
            'detalles.*.producto_id' => 'required|exists:productos,id',
            'detalles.*.cantidad' => 'required|numeric|min:0.01',
            'detalles.*.precio' => 'required|numeric|min:0',
            'detalles.*.descuento' => 'numeric|min:0'
        ]);

        DB::beginTransaction();
        try {
            // Crear compra
            $compra = Compra::create([
                'proveedor_id' => $request->proveedor_id,
                'fecha' => $request->fecha,
                'tipo_comprobante' => $request->tipo_comprobante,
                'ncf' => $request->ncf,
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

                // Crear detalle de compra
                CompraDetalle::create([
                    'compra_id' => $compra->id,
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
                    'tipo' => 'ENTRADA',
                    'cantidad' => $detalle['cantidad'],
                    'precio' => $detalle['precio'],
                    'observacion' => "Compra #{$compra->id}"
                ]);

                // Actualizar costo del producto si ha cambiado
                if ($producto->precio != $detalle['precio']) {
                    $producto->update(['precio' => $detalle['precio']]);
                    
                    // Registrar historial de costo
                    HistorialCosto::create([
                        'producto_id' => $producto->id,
                        'precio' => $detalle['precio'],
                        'observacion' => "Actualización por compra #{$compra->id}"
                    ]);
                }
            }

            // Actualizar total de compra
            $compra->update(['total' => $total]);

            // Crear cuenta por pagar si es a crédito
            $proveedor = \App\Models\Proveedor::findOrFail($request->proveedor_id);
            if ($proveedor->condicionPago->dias > 0) {
                CuentaPorPagar::create([
                    'proveedor_id' => $proveedor->id,
                    'compra_id' => $compra->id,
                    'fecha_vencimiento' => date('Y-m-d', strtotime($request->fecha . " + {$proveedor->condicionPago->dias} days")),
                    'monto' => $total,
                    'estado' => 'PENDIENTE'
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Compra registrada exitosamente',
                'data' => $compra->load(['proveedor', 'detalles.producto'])
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * @OA\Get(
     *     path="/api/compras/{id}",
     *     summary="Obtener detalle de una compra",
     *     tags={"Compras"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la compra",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detalle de la compra",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="numero", type="string"),
     *                 @OA\Property(property="fecha", type="string", format="date"),
     *                 @OA\Property(property="tipo_comprobante", type="string"),
     *                 @OA\Property(property="ncf", type="string"),
     *                 @OA\Property(property="observacion", type="string", nullable=true),
     *                 @OA\Property(property="total", type="number", format="float"),
     *                 @OA\Property(property="proveedor", type="object",
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="nombre", type="string"),
     *                     @OA\Property(property="documento", type="string")
     *                 ),
     *                 @OA\Property(property="detalles", type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="producto_id", type="integer"),
     *                         @OA\Property(property="cantidad", type="number"),
     *                         @OA\Property(property="precio", type="number"),
     *                         @OA\Property(property="descuento", type="number"),
     *                         @OA\Property(property="subtotal", type="number"),
     *                         @OA\Property(property="importe", type="number"),
     *                         @OA\Property(property="producto", type="object",
     *                             @OA\Property(property="id", type="integer"),
     *                             @OA\Property(property="nombre", type="string"),
     *                             @OA\Property(property="codigo", type="string")
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Compra no encontrada"
     *     )
     * )
     */
    public function show($id)
    {
        $compra = Compra::with(['proveedor', 'detalles.producto', 'pagos'])
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $compra
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/compras/{id}",
     *     summary="Anular una compra",
     *     tags={"Compras"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la compra",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compra anulada exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Compra no encontrada"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="No se puede anular la compra"
     *     )
     * )
     */
    public function destroy($id)
    {
        try {
            $compra = Compra::findOrFail($id);
            
            if ($compra->estado === 'ANULADA') {
                return response()->json(['message' => 'La compra ya está anulada'], 400);
            }

            DB::beginTransaction();

            // Revertir el stock
            foreach ($compra->detalles as $detalle) {
                $detalle->producto->actualizarStock($detalle->cantidad, 'SALIDA');
            }

            // Anular la cuenta por pagar si existe
            if ($compra->cuentaPorPagar) {
                $compra->cuentaPorPagar->update(['estado' => 'ANULADA']);
            }

            $compra->estado = 'ANULADA';
            $compra->save();

            DB::commit();
            return response()->json(['message' => 'Compra anulada exitosamente']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
} 