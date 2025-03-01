<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UnidadMedida;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * @OA\Tag(
 *     name="Unidades de Medida",
 *     description="API Endpoints de unidades de medida"
 * )
 */
class UnidadMedidaController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/unidades-medida",
     *     summary="Listar todas las unidades de medida",
     *     tags={"Unidades de Medida"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Lista de unidades de medida obtenida exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="nombre", type="string"),
     *                     @OA\Property(property="abreviatura", type="string"),
     *                     @OA\Property(property="is_activo", type="boolean")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function index()
    {
        $unidadesMedida = UnidadMedida::where('is_activo', true)
            ->orderBy('nombre')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $unidadesMedida
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/unidades-medida",
     *     summary="Crear una nueva unidad de medida",
     *     tags={"Unidades de Medida"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nombre"},
     *             @OA\Property(property="nombre", type="string", maxLength=50),
     *             @OA\Property(property="abreviatura", type="string", maxLength=10)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Unidad de medida creada exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="nombre", type="string"),
     *                 @OA\Property(property="abreviatura", type="string"),
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
            'nombre' => 'required|string|max:50|unique:unidad_medidas',
            'abreviatura' => 'nullable|string|max:10'
        ]);

        $unidadMedida = UnidadMedida::create($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Unidad de medida creada exitosamente',
            'data' => $unidadMedida
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/unidades-medida/{unidadMedida}",
     *     summary="Obtener detalles de una unidad de medida",
     *     tags={"Unidades de Medida"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="unidadMedida",
     *         in="path",
     *         required=true,
     *         description="ID de la unidad de medida",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detalles de la unidad de medida obtenidos exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="nombre", type="string"),
     *                 @OA\Property(property="abreviatura", type="string"),
     *                 @OA\Property(property="is_activo", type="boolean")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unidad de medida no encontrada"
     *     )
     * )
     */
    public function show(UnidadMedida $unidadMedida)
    {
        return response()->json([
            'status' => 'success',
            'data' => $unidadMedida
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/unidades-medida/{unidadMedida}",
     *     summary="Actualizar una unidad de medida",
     *     tags={"Unidades de Medida"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="unidadMedida",
     *         in="path",
     *         required=true,
     *         description="ID de la unidad de medida",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nombre"},
     *             @OA\Property(property="nombre", type="string", maxLength=50),
     *             @OA\Property(property="abreviatura", type="string", maxLength=10),
     *             @OA\Property(property="is_activo", type="boolean")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Unidad de medida actualizada exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="nombre", type="string"),
     *                 @OA\Property(property="abreviatura", type="string"),
     *                 @OA\Property(property="is_activo", type="boolean")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unidad de medida no encontrada"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación"
     *     )
     * )
     */
    public function update(Request $request, UnidadMedida $unidadMedida)
    {
        $request->validate([
            'nombre' => ['required', 'string', 'max:50', Rule::unique('unidad_medidas')->ignore($unidadMedida)],
            'abreviatura' => 'nullable|string|max:10',
            'is_activo' => 'boolean'
        ]);

        $unidadMedida->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Unidad de medida actualizada exitosamente',
            'data' => $unidadMedida
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/unidades-medida/{unidadMedida}",
     *     summary="Eliminar una unidad de medida",
     *     tags={"Unidades de Medida"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="unidadMedida",
     *         in="path",
     *         required=true,
     *         description="ID de la unidad de medida",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Unidad de medida eliminada exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unidad de medida no encontrada"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="No se puede eliminar la unidad de medida"
     *     )
     * )
     */
    public function destroy(UnidadMedida $unidadMedida)
    {
        if ($unidadMedida->productos()->exists() || $unidadMedida->detalleUnidadProductos()->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No se puede eliminar la unidad de medida porque está siendo utilizada por productos'
            ], 422);
        }

        $unidadMedida->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Unidad de medida eliminada exitosamente'
        ]);
    }
} 