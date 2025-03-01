<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Venta;
use App\Models\Compra;
use App\Models\Producto;
use App\Models\Cliente;
use App\Models\Proveedor;
use App\Models\MovimientoInventario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(
 *     name="Reportes",
 *     description="API Endpoints de reportes del sistema"
 * )
 */
class ReporteController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/reportes/ventas-por-periodo",
     *     summary="Reporte de ventas por período",
     *     tags={"Reportes"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="fecha_inicio",
     *         in="query",
     *         required=true,
     *         description="Fecha inicial del período (YYYY-MM-DD)",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="fecha_fin",
     *         in="query",
     *         required=true,
     *         description="Fecha final del período (YYYY-MM-DD)",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="cliente_id",
     *         in="query",
     *         required=false,
     *         description="ID del cliente para filtrar",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Reporte de ventas generado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="ventas", type="array",
     *                     @OA\Items(type="object",
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="fecha", type="string", format="date"),
     *                         @OA\Property(property="total", type="number"),
     *                         @OA\Property(property="metodo_pago", type="string"),
     *                         @OA\Property(property="cliente", type="object")
     *                     )
     *                 ),
     *                 @OA\Property(property="totales", type="object",
     *                     @OA\Property(property="cantidad_ventas", type="integer"),
     *                     @OA\Property(property="total_ventas", type="number"),
     *                     @OA\Property(property="total_efectivo", type="number"),
     *                     @OA\Property(property="total_credito", type="number"),
     *                     @OA\Property(property="total_otros", type="number")
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
    public function ventasPorPeriodo(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'cliente_id' => 'nullable|exists:clientes,id'
        ]);

        $query = Venta::with(['cliente', 'detalles.producto'])
            ->where('estado', '!=', 'ANULADA')
            ->whereBetween('fecha', [$request->fecha_inicio, $request->fecha_fin]);

        if ($request->cliente_id) {
            $query->where('cliente_id', $request->cliente_id);
        }

        $ventas = $query->get();

        $totales = [
            'cantidad_ventas' => $ventas->count(),
            'total_ventas' => $ventas->sum('total'),
            'total_efectivo' => $ventas->whereIn('metodo_pago', ['EFECTIVO'])->sum('total'),
            'total_credito' => $ventas->whereIn('metodo_pago', ['CREDITO'])->sum('total'),
            'total_otros' => $ventas->whereNotIn('metodo_pago', ['EFECTIVO', 'CREDITO'])->sum('total')
        ];

        return response()->json([
            'status' => 'success',
            'data' => [
                'ventas' => $ventas,
                'totales' => $totales
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/reportes/compras-por-periodo",
     *     summary="Reporte de compras por período",
     *     tags={"Reportes"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="fecha_inicio",
     *         in="query",
     *         required=true,
     *         description="Fecha inicial del período (YYYY-MM-DD)",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="fecha_fin",
     *         in="query",
     *         required=true,
     *         description="Fecha final del período (YYYY-MM-DD)",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="proveedor_id",
     *         in="query",
     *         required=false,
     *         description="ID del proveedor para filtrar",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Reporte de compras generado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="compras", type="array",
     *                     @OA\Items(type="object",
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="fecha", type="string", format="date"),
     *                         @OA\Property(property="total", type="number"),
     *                         @OA\Property(property="metodo_pago", type="string"),
     *                         @OA\Property(property="proveedor", type="object")
     *                     )
     *                 ),
     *                 @OA\Property(property="totales", type="object",
     *                     @OA\Property(property="cantidad_compras", type="integer"),
     *                     @OA\Property(property="total_compras", type="number"),
     *                     @OA\Property(property="total_efectivo", type="number"),
     *                     @OA\Property(property="total_credito", type="number"),
     *                     @OA\Property(property="total_otros", type="number")
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
    public function comprasPorPeriodo(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'proveedor_id' => 'nullable|exists:proveedores,id'
        ]);

        $query = Compra::with(['proveedor', 'detalles.producto'])
            ->where('estado', '!=', 'ANULADA')
            ->whereBetween('fecha', [$request->fecha_inicio, $request->fecha_fin]);

        if ($request->proveedor_id) {
            $query->where('proveedor_id', $request->proveedor_id);
        }

        $compras = $query->get();

        $totales = [
            'cantidad_compras' => $compras->count(),
            'total_compras' => $compras->sum('total'),
            'total_efectivo' => $compras->whereIn('metodo_pago', ['EFECTIVO'])->sum('total'),
            'total_credito' => $compras->whereIn('metodo_pago', ['CREDITO'])->sum('total'),
            'total_otros' => $compras->whereNotIn('metodo_pago', ['EFECTIVO', 'CREDITO'])->sum('total')
        ];

        return response()->json([
            'status' => 'success',
            'data' => [
                'compras' => $compras,
                'totales' => $totales
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/reportes/inventario-actual",
     *     summary="Reporte de inventario actual",
     *     tags={"Reportes"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="categoria_id",
     *         in="query",
     *         required=false,
     *         description="ID de la categoría para filtrar",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="incluir_inactivos",
     *         in="query",
     *         required=false,
     *         description="Incluir productos inactivos",
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Reporte de inventario generado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="productos", type="array",
     *                     @OA\Items(type="object",
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="nombre", type="string"),
     *                         @OA\Property(property="codigo", type="string"),
     *                         @OA\Property(property="stock_actual", type="number"),
     *                         @OA\Property(property="stock_minimo", type="number"),
     *                         @OA\Property(property="costo", type="number"),
     *                         @OA\Property(property="valor_inventario", type="number"),
     *                         @OA\Property(property="categoria", type="object")
     *                     )
     *                 ),
     *                 @OA\Property(property="totales", type="object",
     *                     @OA\Property(property="cantidad_productos", type="integer"),
     *                     @OA\Property(property="valor_total_inventario", type="number"),
     *                     @OA\Property(property="productos_bajo_minimo", type="integer")
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
    public function inventarioActual(Request $request)
    {
        $request->validate([
            'categoria_id' => 'nullable|exists:categorias,id',
            'incluir_inactivos' => 'nullable|boolean'
        ]);

        $query = Producto::with(['categoria', 'unidadMedidas', 'movimientosInventario'])
            ->when(!$request->incluir_inactivos, function ($q) {
                return $q->where('is_activo', true);
            })
            ->when($request->categoria_id, function ($q) use ($request) {
                return $q->where('categoria_id', $request->categoria_id);
            });

        $productos = $query->get();

        $productos->each(function ($producto) {
            $producto->stock_actual = $producto->movimientosInventario
                ->where('tipo', 'ENTRADA')
                ->sum('cantidad') - 
                $producto->movimientosInventario
                ->where('tipo', 'SALIDA')
                ->sum('cantidad');

            $producto->valor_inventario = $producto->stock_actual * $producto->costo;
        });

        $totales = [
            'cantidad_productos' => $productos->count(),
            'valor_total_inventario' => $productos->sum('valor_inventario'),
            'productos_bajo_minimo' => $productos->filter(function ($producto) {
                return $producto->stock_actual < $producto->stock_minimo;
            })->count()
        ];

        return response()->json([
            'status' => 'success',
            'data' => [
                'productos' => $productos,
                'totales' => $totales
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/reportes/cuentas-por-cobrar",
     *     summary="Reporte de cuentas por cobrar",
     *     tags={"Reportes"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="cliente_id",
     *         in="query",
     *         required=false,
     *         description="ID del cliente para filtrar",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="estado",
     *         in="query",
     *         required=false,
     *         description="Estado de las cuentas",
     *         @OA\Schema(type="string", enum={"PENDIENTE", "PAGADA", "ANULADA"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Reporte de cuentas por cobrar generado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="clientes", type="array",
     *                     @OA\Items(type="object",
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="nombre", type="string"),
     *                         @OA\Property(property="total_por_cobrar", type="number"),
     *                         @OA\Property(property="total_vencido", type="number"),
     *                         @OA\Property(property="cuentasPorCobrar", type="array",
     *                             @OA\Items(type="object")
     *                         )
     *                     )
     *                 ),
     *                 @OA\Property(property="totales", type="object",
     *                     @OA\Property(property="total_por_cobrar", type="number"),
     *                     @OA\Property(property="total_vencido", type="number")
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
    public function cuentasPorCobrar(Request $request)
    {
        $request->validate([
            'cliente_id' => 'nullable|exists:clientes,id',
            'estado' => 'nullable|in:PENDIENTE,PAGADA,ANULADA'
        ]);

        $query = Cliente::with(['cuentasPorCobrar' => function ($q) use ($request) {
            if ($request->estado) {
                $q->where('estado', $request->estado);
            }
            $q->with(['venta', 'pagos']);
        }]);

        if ($request->cliente_id) {
            $query->where('id', $request->cliente_id);
        }

        $clientes = $query->get();

        $clientes->each(function ($cliente) {
            $cliente->total_por_cobrar = $cliente->cuentasPorCobrar
                ->where('estado', 'PENDIENTE')
                ->sum('monto');
            
            $cliente->total_vencido = $cliente->cuentasPorCobrar
                ->where('estado', 'PENDIENTE')
                ->where('fecha_vencimiento', '<', now())
                ->sum('monto');
        });

        $totales = [
            'total_por_cobrar' => $clientes->sum('total_por_cobrar'),
            'total_vencido' => $clientes->sum('total_vencido')
        ];

        return response()->json([
            'status' => 'success',
            'data' => [
                'clientes' => $clientes,
                'totales' => $totales
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/reportes/cuentas-por-pagar",
     *     summary="Reporte de cuentas por pagar",
     *     tags={"Reportes"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="proveedor_id",
     *         in="query",
     *         required=false,
     *         description="ID del proveedor para filtrar",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="estado",
     *         in="query",
     *         required=false,
     *         description="Estado de las cuentas",
     *         @OA\Schema(type="string", enum={"PENDIENTE", "PAGADA", "ANULADA"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Reporte de cuentas por pagar generado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="proveedores", type="array",
     *                     @OA\Items(type="object",
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="nombre", type="string"),
     *                         @OA\Property(property="total_por_pagar", type="number"),
     *                         @OA\Property(property="total_vencido", type="number"),
     *                         @OA\Property(property="cuentasPorPagar", type="array",
     *                             @OA\Items(type="object")
     *                         )
     *                     )
     *                 ),
     *                 @OA\Property(property="totales", type="object",
     *                     @OA\Property(property="total_por_pagar", type="number"),
     *                     @OA\Property(property="total_vencido", type="number")
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
    public function cuentasPorPagar(Request $request)
    {
        $request->validate([
            'proveedor_id' => 'nullable|exists:proveedores,id',
            'estado' => 'nullable|in:PENDIENTE,PAGADA,ANULADA'
        ]);

        $query = Proveedor::with(['cuentasPorPagar' => function ($q) use ($request) {
            if ($request->estado) {
                $q->where('estado', $request->estado);
            }
            $q->with(['compra', 'pagos']);
        }]);

        if ($request->proveedor_id) {
            $query->where('id', $request->proveedor_id);
        }

        $proveedores = $query->get();

        $proveedores->each(function ($proveedor) {
            $proveedor->total_por_pagar = $proveedor->cuentasPorPagar
                ->where('estado', 'PENDIENTE')
                ->sum('monto');
            
            $proveedor->total_vencido = $proveedor->cuentasPorPagar
                ->where('estado', 'PENDIENTE')
                ->where('fecha_vencimiento', '<', now())
                ->sum('monto');
        });

        $totales = [
            'total_por_pagar' => $proveedores->sum('total_por_pagar'),
            'total_vencido' => $proveedores->sum('total_vencido')
        ];

        return response()->json([
            'status' => 'success',
            'data' => [
                'proveedores' => $proveedores,
                'totales' => $totales
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/reportes/movimientos-inventario",
     *     summary="Reporte de movimientos de inventario",
     *     tags={"Reportes"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="fecha_inicio",
     *         in="query",
     *         required=true,
     *         description="Fecha inicial del período (YYYY-MM-DD)",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="fecha_fin",
     *         in="query",
     *         required=true,
     *         description="Fecha final del período (YYYY-MM-DD)",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="producto_id",
     *         in="query",
     *         required=false,
     *         description="ID del producto para filtrar",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="tipo",
     *         in="query",
     *         required=false,
     *         description="Tipo de movimiento",
     *         @OA\Schema(type="string", enum={"ENTRADA", "SALIDA", "AJUSTE", "INICIAL"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Reporte de movimientos generado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="movimientos", type="array",
     *                     @OA\Items(type="object",
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="tipo", type="string"),
     *                         @OA\Property(property="cantidad", type="number"),
     *                         @OA\Property(property="created_at", type="string", format="date-time"),
     *                         @OA\Property(property="producto", type="object")
     *                     )
     *                 ),
     *                 @OA\Property(property="totales", type="object",
     *                     @OA\Property(property="total_entradas", type="number"),
     *                     @OA\Property(property="total_salidas", type="number"),
     *                     @OA\Property(property="total_ajustes", type="number")
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
    public function movimientosInventario(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'producto_id' => 'nullable|exists:productos,id',
            'tipo' => 'nullable|in:ENTRADA,SALIDA,AJUSTE,INICIAL'
        ]);

        $query = MovimientoInventario::with(['producto.categoria'])
            ->whereBetween('created_at', [$request->fecha_inicio, $request->fecha_fin]);

        if ($request->producto_id) {
            $query->where('producto_id', $request->producto_id);
        }

        if ($request->tipo) {
            $query->where('tipo', $request->tipo);
        }

        $movimientos = $query->orderBy('created_at', 'desc')->get();

        $totales = [
            'total_entradas' => $movimientos->where('tipo', 'ENTRADA')->sum('cantidad'),
            'total_salidas' => $movimientos->where('tipo', 'SALIDA')->sum('cantidad'),
            'total_ajustes' => $movimientos->where('tipo', 'AJUSTE')->sum('cantidad')
        ];

        return response()->json([
            'status' => 'success',
            'data' => [
                'movimientos' => $movimientos,
                'totales' => $totales
            ]
        ]);
    }
} 