<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * @OA\Tag(
 *     name="Clientes",
 *     description="API Endpoints de clientes"
 * )
 */
class ClienteController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/clientes",
     *     summary="Listar todos los clientes",
     *     tags={"Clientes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Lista de clientes obtenida exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="nombre", type="string"),
     *                     @OA\Property(property="tipo_documento", type="string"),
     *                     @OA\Property(property="documento", type="string"),
     *                     @OA\Property(property="email", type="string"),
     *                     @OA\Property(property="telefono", type="string"),
     *                     @OA\Property(property="direccion", type="string")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function index()
    {
        $clientes = Cliente::where('is_activo', true)
            ->orderBy('nombre')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $clientes
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/clientes",
     *     summary="Crear un nuevo cliente",
     *     tags={"Clientes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nombre","tipo_documento","documento","direccion","condicion_pago_id"},
     *             @OA\Property(property="nombre", type="string", maxLength=100),
     *             @OA\Property(property="tipo_documento", type="string", enum={"RNC","CEDULA"}),
     *             @OA\Property(property="documento", type="string", maxLength=20),
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="telefono", type="string", maxLength=20),
     *             @OA\Property(property="direccion", type="string", maxLength=255),
     *             @OA\Property(property="condicion_pago_id", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Cliente creado exitosamente"
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
            'documento' => ['required', 'string', 'max:20', Rule::unique('clientes')],
            'email' => 'nullable|email|max:100',
            'telefono' => 'nullable|string|max:20',
            'direccion' => 'required|string|max:255',
            'condicion_pago_id' => 'required|exists:condicion_pagos,id'
        ]);

        $cliente = Cliente::create($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Cliente creado exitosamente',
            'data' => $cliente->load('condicionPago')
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/clientes/{cliente}",
     *     summary="Obtener detalles de un cliente",
     *     tags={"Clientes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="cliente",
     *         in="path",
     *         required=true,
     *         description="ID del cliente",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detalles del cliente obtenidos exitosamente"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Cliente no encontrado"
     *     )
     * )
     */
    public function show(Cliente $cliente)
    {
        return response()->json([
            'status' => 'success',
            'data' => $cliente->load(['condicionPago', 'ventas', 'cuentasPorCobrar'])
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/clientes/{cliente}",
     *     summary="Actualizar un cliente",
     *     tags={"Clientes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="cliente",
     *         in="path",
     *         required=true,
     *         description="ID del cliente",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nombre","tipo_documento","documento","direccion","condicion_pago_id"},
     *             @OA\Property(property="nombre", type="string", maxLength=100),
     *             @OA\Property(property="tipo_documento", type="string", enum={"RNC","CEDULA"}),
     *             @OA\Property(property="documento", type="string", maxLength=20),
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="telefono", type="string", maxLength=20),
     *             @OA\Property(property="direccion", type="string", maxLength=255),
     *             @OA\Property(property="condicion_pago_id", type="integer"),
     *             @OA\Property(property="is_activo", type="boolean")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Cliente actualizado exitosamente"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación"
     *     )
     * )
     */
    public function update(Request $request, Cliente $cliente)
    {
        $request->validate([
            'nombre' => 'required|string|max:100',
            'tipo_documento' => 'required|string|in:RNC,CEDULA',
            'documento' => ['required', 'string', 'max:20', Rule::unique('clientes')->ignore($cliente)],
            'email' => 'nullable|email|max:100',
            'telefono' => 'nullable|string|max:20',
            'direccion' => 'required|string|max:255',
            'condicion_pago_id' => 'required|exists:condicion_pagos,id',
            'is_activo' => 'boolean'
        ]);

        $cliente->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Cliente actualizado exitosamente',
            'data' => $cliente->load('condicionPago')
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/clientes/{cliente}",
     *     summary="Eliminar un cliente",
     *     tags={"Clientes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="cliente",
     *         in="path",
     *         required=true,
     *         description="ID del cliente",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Cliente eliminado exitosamente"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="No se puede eliminar el cliente"
     *     )
     * )
     */
    public function destroy(Cliente $cliente)
    {
        if ($cliente->ventas()->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No se puede eliminar el cliente porque tiene ventas asociadas'
            ], 422);
        }

        if ($cliente->cuentasPorCobrar()->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No se puede eliminar el cliente porque tiene cuentas por cobrar pendientes'
            ], 422);
        }

        $cliente->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Cliente eliminado exitosamente'
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/clientes/{cliente}/balance",
     *     summary="Obtener el balance de un cliente",
     *     tags={"Clientes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="cliente",
     *         in="path",
     *         required=true,
     *         description="ID del cliente",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Balance del cliente obtenido exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="total_ventas", type="number"),
     *                 @OA\Property(property="total_pagado", type="number"),
     *                 @OA\Property(property="balance_pendiente", type="number")
     *             )
     *         )
     *     )
     * )
     */
    public function getBalance(Cliente $cliente)
    {
        $totalVentas = $cliente->ventas()->sum('total');
        $totalPagado = $cliente->cuentasPorCobrar()->where('estado', 'PAGADA')->sum('monto');
        $balance = $totalVentas - $totalPagado;

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_ventas' => $totalVentas,
                'total_pagado' => $totalPagado,
                'balance_pendiente' => $balance
            ]
        ]);
    }
} 