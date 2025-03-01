<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * @OA\Tag(
 *     name="Proveedores",
 *     description="API Endpoints de proveedores"
 * )
 */
class ProveedorController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/proveedores",
     *     summary="Listar todos los proveedores",
     *     tags={"Proveedores"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Lista de proveedores obtenida exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="nombre", type="string"),
     *                     @OA\Property(property="tipo_documento", type="string", enum={"RNC", "CEDULA"}),
     *                     @OA\Property(property="documento", type="string"),
     *                     @OA\Property(property="email", type="string", nullable=true),
     *                     @OA\Property(property="telefono", type="string", nullable=true),
     *                     @OA\Property(property="direccion", type="string"),
     *                     @OA\Property(property="condicion_pago_id", type="integer"),
     *                     @OA\Property(property="is_activo", type="boolean")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function index()
    {
        $proveedores = Proveedor::where('is_activo', true)
            ->orderBy('nombre')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $proveedores
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/proveedores",
     *     summary="Crear un nuevo proveedor",
     *     tags={"Proveedores"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nombre", "tipo_documento", "documento", "direccion", "condicion_pago_id"},
     *             @OA\Property(property="nombre", type="string", maxLength=100),
     *             @OA\Property(property="tipo_documento", type="string", enum={"RNC", "CEDULA"}),
     *             @OA\Property(property="documento", type="string", maxLength=20),
     *             @OA\Property(property="email", type="string", format="email", maxLength=100, nullable=true),
     *             @OA\Property(property="telefono", type="string", maxLength=20, nullable=true),
     *             @OA\Property(property="direccion", type="string", maxLength=255),
     *             @OA\Property(property="condicion_pago_id", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Proveedor creado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="nombre", type="string"),
     *                 @OA\Property(property="tipo_documento", type="string"),
     *                 @OA\Property(property="documento", type="string"),
     *                 @OA\Property(property="email", type="string", nullable=true),
     *                 @OA\Property(property="telefono", type="string", nullable=true),
     *                 @OA\Property(property="direccion", type="string"),
     *                 @OA\Property(property="condicion_pago_id", type="integer"),
     *                 @OA\Property(property="is_activo", type="boolean"),
     *                 @OA\Property(property="condicionPago", type="object",
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="nombre", type="string"),
     *                     @OA\Property(property="dias", type="integer")
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
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:100',
            'tipo_documento' => 'required|string|in:RNC,CEDULA',
            'documento' => ['required', 'string', 'max:20', Rule::unique('proveedores')],
            'email' => 'nullable|email|max:100',
            'telefono' => 'nullable|string|max:20',
            'direccion' => 'required|string|max:255',
            'condicion_pago_id' => 'required|exists:condicion_pagos,id'
        ]);

        $proveedor = Proveedor::create($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Proveedor creado exitosamente',
            'data' => $proveedor->load('condicionPago')
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/proveedores/{proveedor}",
     *     summary="Obtener detalles de un proveedor",
     *     tags={"Proveedores"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="proveedor",
     *         in="path",
     *         required=true,
     *         description="ID del proveedor",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detalles del proveedor obtenidos exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="nombre", type="string"),
     *                 @OA\Property(property="tipo_documento", type="string"),
     *                 @OA\Property(property="documento", type="string"),
     *                 @OA\Property(property="email", type="string", nullable=true),
     *                 @OA\Property(property="telefono", type="string", nullable=true),
     *                 @OA\Property(property="direccion", type="string"),
     *                 @OA\Property(property="condicion_pago_id", type="integer"),
     *                 @OA\Property(property="is_activo", type="boolean"),
     *                 @OA\Property(property="condicionPago", type="object",
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="nombre", type="string"),
     *                     @OA\Property(property="dias", type="integer")
     *                 ),
     *                 @OA\Property(property="compras", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="cuentasPorPagar", type="array", @OA\Items(type="object"))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Proveedor no encontrado"
     *     )
     * )
     */
    public function show(Proveedor $proveedor)
    {
        return response()->json([
            'status' => 'success',
            'data' => $proveedor->load(['condicionPago', 'compras', 'cuentasPorPagar'])
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/proveedores/{proveedor}",
     *     summary="Actualizar un proveedor",
     *     tags={"Proveedores"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="proveedor",
     *         in="path",
     *         required=true,
     *         description="ID del proveedor",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nombre", "tipo_documento", "documento", "direccion", "condicion_pago_id"},
     *             @OA\Property(property="nombre", type="string", maxLength=100),
     *             @OA\Property(property="tipo_documento", type="string", enum={"RNC", "CEDULA"}),
     *             @OA\Property(property="documento", type="string", maxLength=20),
     *             @OA\Property(property="email", type="string", format="email", maxLength=100, nullable=true),
     *             @OA\Property(property="telefono", type="string", maxLength=20, nullable=true),
     *             @OA\Property(property="direccion", type="string", maxLength=255),
     *             @OA\Property(property="condicion_pago_id", type="integer"),
     *             @OA\Property(property="is_activo", type="boolean")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Proveedor actualizado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="nombre", type="string"),
     *                 @OA\Property(property="tipo_documento", type="string"),
     *                 @OA\Property(property="documento", type="string"),
     *                 @OA\Property(property="email", type="string", nullable=true),
     *                 @OA\Property(property="telefono", type="string", nullable=true),
     *                 @OA\Property(property="direccion", type="string"),
     *                 @OA\Property(property="condicion_pago_id", type="integer"),
     *                 @OA\Property(property="is_activo", type="boolean"),
     *                 @OA\Property(property="condicionPago", type="object",
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="nombre", type="string"),
     *                     @OA\Property(property="dias", type="integer")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Proveedor no encontrado"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación"
     *     )
     * )
     */
    public function update(Request $request, Proveedor $proveedor)
    {
        $request->validate([
            'nombre' => 'required|string|max:100',
            'tipo_documento' => 'required|string|in:RNC,CEDULA',
            'documento' => ['required', 'string', 'max:20', Rule::unique('proveedores')->ignore($proveedor)],
            'email' => 'nullable|email|max:100',
            'telefono' => 'nullable|string|max:20',
            'direccion' => 'required|string|max:255',
            'condicion_pago_id' => 'required|exists:condicion_pagos,id',
            'is_activo' => 'boolean'
        ]);

        $proveedor->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Proveedor actualizado exitosamente',
            'data' => $proveedor->load('condicionPago')
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/proveedores/{proveedor}",
     *     summary="Eliminar un proveedor",
     *     tags={"Proveedores"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="proveedor",
     *         in="path",
     *         required=true,
     *         description="ID del proveedor",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Proveedor eliminado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Proveedor no encontrado"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="No se puede eliminar el proveedor"
     *     )
     * )
     */
    public function destroy(Proveedor $proveedor)
    {
        if ($proveedor->compras()->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No se puede eliminar el proveedor porque tiene compras asociadas'
            ], 422);
        }

        if ($proveedor->cuentasPorPagar()->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No se puede eliminar el proveedor porque tiene cuentas por pagar pendientes'
            ], 422);
        }

        $proveedor->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Proveedor eliminado exitosamente'
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/proveedores/{proveedor}/balance",
     *     summary="Obtener el balance de un proveedor",
     *     tags={"Proveedores"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="proveedor",
     *         in="path",
     *         required=true,
     *         description="ID del proveedor",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Balance del proveedor obtenido exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="total_compras", type="number", format="float"),
     *                 @OA\Property(property="total_pagado", type="number", format="float"),
     *                 @OA\Property(property="balance_pendiente", type="number", format="float")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Proveedor no encontrado"
     *     )
     * )
     */
    public function getBalance(Proveedor $proveedor)
    {
        $totalCompras = $proveedor->compras()->sum('total');
        $totalPagado = $proveedor->cuentasPorPagar()->where('estado', 'PAGADA')->sum('monto');
        $balance = $totalCompras - $totalPagado;

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_compras' => $totalCompras,
                'total_pagado' => $totalPagado,
                'balance_pendiente' => $balance
            ]
        ]);
    }
} 