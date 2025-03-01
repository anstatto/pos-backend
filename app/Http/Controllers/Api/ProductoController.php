<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use App\Models\DetalleUnidadProducto;
use App\Models\MovimientoInventario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * @OA\Tag(
 *     name="Productos",
 *     description="API Endpoints de productos"
 * )
 */
class ProductoController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/productos",
     *     summary="Listar todos los productos",
     *     tags={"Productos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Término de búsqueda",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="categoria_id",
     *         in="query",
     *         description="Filtrar por categoría",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de productos obtenida exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="codigo", type="string"),
     *                     @OA\Property(property="nombre", type="string"),
     *                     @OA\Property(property="descripcion", type="string"),
     *                     @OA\Property(property="precio", type="number"),
     *                     @OA\Property(property="stock", type="integer"),
     *                     @OA\Property(property="categoria", type="object")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function index()
    {
        $productos = Producto::with(['categoria', 'unidadMedidas'])
            ->where('is_activo', true)
            ->orderBy('nombre')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $productos
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/productos",
     *     summary="Crear un nuevo producto",
     *     tags={"Productos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"codigo","nombre","precio","categoria_id"},
     *             @OA\Property(property="codigo", type="string"),
     *             @OA\Property(property="nombre", type="string"),
     *             @OA\Property(property="descripcion", type="string"),
     *             @OA\Property(property="precio", type="number"),
     *             @OA\Property(property="stock", type="integer"),
     *             @OA\Property(property="categoria_id", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Producto creado exitosamente"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:100',
            'codigo' => ['required', 'string', 'max:50', Rule::unique('productos')],
            'descripcion' => 'nullable|string',
            'categoria_id' => 'required|exists:categorias,id',
            'precio_venta' => 'required|numeric|min:0',
            'costo' => 'required|numeric|min:0',
            'stock_minimo' => 'required|integer|min:0',
            'unidad_medidas' => 'required|array|min:1',
            'unidad_medidas.*.unidad_medida_id' => 'required|exists:unidad_medidas,id',
            'unidad_medidas.*.factor_conversion' => 'required|numeric|min:0.01',
            'unidad_medidas.*.is_unidad_principal' => 'required|boolean'
        ]);

        DB::beginTransaction();
        try {
            $producto = Producto::create($request->except('unidad_medidas'));

            foreach ($request->unidad_medidas as $unidad) {
                DetalleUnidadProducto::create([
                    'producto_id' => $producto->id,
                    'unidad_medida_id' => $unidad['unidad_medida_id'],
                    'factor_conversion' => $unidad['factor_conversion'],
                    'is_unidad_principal' => $unidad['is_unidad_principal']
                ]);
            }

            // Registrar movimiento inicial de inventario
            MovimientoInventario::create([
                'producto_id' => $producto->id,
                'tipo' => 'INICIAL',
                'cantidad' => 0,
                'costo' => $producto->costo,
                'observacion' => 'Creación inicial del producto'
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Producto creado exitosamente',
                'data' => $producto->load(['categoria', 'unidadMedidas'])
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * @OA\Get(
     *     path="/api/productos/{id}",
     *     summary="Obtener detalles de un producto",
     *     tags={"Productos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detalles del producto"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Producto no encontrado"
     *     )
     * )
     */
    public function show(Producto $producto)
    {
        return response()->json([
            'status' => 'success',
            'data' => $producto->load([
                'categoria', 
                'unidadMedidas',
                'movimientosInventario',
                'historialCostos'
            ])
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/productos/{id}",
     *     summary="Actualizar un producto",
     *     tags={"Productos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="codigo", type="string"),
     *             @OA\Property(property="nombre", type="string"),
     *             @OA\Property(property="descripcion", type="string"),
     *             @OA\Property(property="precio", type="number"),
     *             @OA\Property(property="stock", type="integer"),
     *             @OA\Property(property="categoria_id", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Producto actualizado exitosamente"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Producto no encontrado"
     *     )
     * )
     */
    public function update(Request $request, Producto $producto)
    {
        $request->validate([
            'nombre' => 'required|string|max:100',
            'codigo' => ['required', 'string', 'max:50', Rule::unique('productos')->ignore($producto)],
            'descripcion' => 'nullable|string',
            'categoria_id' => 'required|exists:categorias,id',
            'precio_venta' => 'required|numeric|min:0',
            'stock_minimo' => 'required|integer|min:0',
            'is_activo' => 'boolean',
            'unidad_medidas' => 'required|array|min:1',
            'unidad_medidas.*.unidad_medida_id' => 'required|exists:unidad_medidas,id',
            'unidad_medidas.*.factor_conversion' => 'required|numeric|min:0.01',
            'unidad_medidas.*.is_unidad_principal' => 'required|boolean'
        ]);

        DB::beginTransaction();
        try {
            $producto->update($request->except('unidad_medidas'));

            // Actualizar unidades de medida
            $producto->unidadMedidas()->sync([]);
            foreach ($request->unidad_medidas as $unidad) {
                DetalleUnidadProducto::create([
                    'producto_id' => $producto->id,
                    'unidad_medida_id' => $unidad['unidad_medida_id'],
                    'factor_conversion' => $unidad['factor_conversion'],
                    'is_unidad_principal' => $unidad['is_unidad_principal']
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Producto actualizado exitosamente',
                'data' => $producto->load(['categoria', 'unidadMedidas'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/productos/{id}",
     *     summary="Eliminar un producto",
     *     tags={"Productos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Producto eliminado exitosamente"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Producto no encontrado"
     *     )
     * )
     */
    public function destroy(Producto $producto)
    {
        if ($producto->ventaDetalles()->exists() || $producto->compraDetalles()->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No se puede eliminar el producto porque tiene movimientos asociados'
            ], 422);
        }

        DB::beginTransaction();
        try {
            $producto->unidadMedidas()->sync([]);
            $producto->movimientosInventario()->delete();
            $producto->historialCostos()->delete();
            $producto->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Producto eliminado exitosamente'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function ajustarInventario(Request $request, Producto $producto)
    {
        $request->validate([
            'cantidad' => 'required|numeric',
            'tipo' => 'required|in:ENTRADA,SALIDA,AJUSTE',
            'observacion' => 'required|string'
        ]);

        DB::beginTransaction();
        try {
            MovimientoInventario::create([
                'producto_id' => $producto->id,
                'tipo' => $request->tipo,
                'cantidad' => $request->cantidad,
                'costo' => $producto->costo,
                'observacion' => $request->observacion
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Inventario ajustado exitosamente',
                'data' => $producto->fresh(['movimientosInventario'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function actualizarCosto(Request $request, Producto $producto)
    {
        $request->validate([
            'costo' => 'required|numeric|min:0',
            'observacion' => 'required|string'
        ]);

        DB::beginTransaction();
        try {
            $producto->update(['costo' => $request->costo]);

            $producto->historialCostos()->create([
                'costo' => $request->costo,
                'observacion' => $request->observacion
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Costo actualizado exitosamente',
                'data' => $producto->fresh(['historialCostos'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
} 