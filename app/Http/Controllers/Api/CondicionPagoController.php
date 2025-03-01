<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CondicionPago;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * @OA\Tag(
 *     name="Condiciones de Pago",
 *     description="API Endpoints de condiciones de pago"
 * )
 */
class CondicionPagoController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/condiciones-pago",
     *     summary="Listar todas las condiciones de pago",
     *     tags={"Condiciones de Pago"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Lista de condiciones de pago obtenida exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="descripcion", type="string"),
     *                     @OA\Property(property="dias", type="integer"),
     *                     @OA\Property(property="is_activo", type="boolean")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function index()
    {
        $condicionesPago = CondicionPago::where('is_activo', true)
            ->orderBy('descripcion')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $condicionesPago
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/condiciones-pago",
     *     summary="Crear una nueva condición de pago",
     *     tags={"Condiciones de Pago"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"descripcion","dias"},
     *             @OA\Property(property="descripcion", type="string", maxLength=100),
     *             @OA\Property(property="dias", type="integer", minimum=0)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Condición de pago creada exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="descripcion", type="string"),
     *                 @OA\Property(property="dias", type="integer"),
     *                 @OA\Property(property="is_activo", type="boolean")
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
            'descripcion' => 'required|string|max:100|unique:condicion_pagos',
            'dias' => 'required|integer|min:0'
        ]);

        $condicionPago = CondicionPago::create($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Condición de pago creada exitosamente',
            'data' => $condicionPago
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/condiciones-pago/{condicionPago}",
     *     summary="Obtener detalles de una condición de pago",
     *     tags={"Condiciones de Pago"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="condicionPago",
     *         in="path",
     *         required=true,
     *         description="ID de la condición de pago",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detalles de la condición de pago obtenidos exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="descripcion", type="string"),
     *                 @OA\Property(property="dias", type="integer"),
     *                 @OA\Property(property="is_activo", type="boolean")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Condición de pago no encontrada"
     *     )
     * )
     */
    public function show(CondicionPago $condicionPago)
    {
        return response()->json([
            'status' => 'success',
            'data' => $condicionPago
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/condiciones-pago/{condicionPago}",
     *     summary="Actualizar una condición de pago",
     *     tags={"Condiciones de Pago"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="condicionPago",
     *         in="path",
     *         required=true,
     *         description="ID de la condición de pago",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"descripcion","dias"},
     *             @OA\Property(property="descripcion", type="string", maxLength=100),
     *             @OA\Property(property="dias", type="integer", minimum=0),
     *             @OA\Property(property="is_activo", type="boolean")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Condición de pago actualizada exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="descripcion", type="string"),
     *                 @OA\Property(property="dias", type="integer"),
     *                 @OA\Property(property="is_activo", type="boolean")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Condición de pago no encontrada"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación"
     *     )
     * )
     */
    public function update(Request $request, CondicionPago $condicionPago)
    {
        $request->validate([
            'descripcion' => ['required', 'string', 'max:100', Rule::unique('condicion_pagos')->ignore($condicionPago)],
            'dias' => 'required|integer|min:0',
            'is_activo' => 'boolean'
        ]);

        $condicionPago->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Condición de pago actualizada exitosamente',
            'data' => $condicionPago
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/condiciones-pago/{condicionPago}",
     *     summary="Eliminar una condición de pago",
     *     tags={"Condiciones de Pago"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="condicionPago",
     *         in="path",
     *         required=true,
     *         description="ID de la condición de pago",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Condición de pago eliminada exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Condición de pago no encontrada"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="No se puede eliminar la condición de pago"
     *     )
     * )
     */
    public function destroy(CondicionPago $condicionPago)
    {
        if ($condicionPago->clientes()->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No se puede eliminar la condición de pago porque está siendo utilizada por clientes'
            ], 422);
        }

        $condicionPago->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Condición de pago eliminada exitosamente'
        ]);
    }
} 