<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * @OA\Tag(
 *     name="Categorías",
 *     description="API Endpoints de categorías de productos"
 * )
 */
class CategoriaController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/categorias",
     *     summary="Listar todas las categorías",
     *     tags={"Categorías"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Lista de categorías obtenida exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="name", type="string"),
     *                     @OA\Property(property="descripcion", type="string"),
     *                     @OA\Property(property="is_activo", type="boolean")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function index()
    {
        $categorias = Categoria::where('is_activo', true)
            ->orderBy('name')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $categorias
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/categorias",
     *     summary="Crear una nueva categoría",
     *     tags={"Categorías"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", maxLength=100),
     *             @OA\Property(property="descripcion", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Categoría creada exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="descripcion", type="string"),
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
            'name' => 'required|string|max:100|unique:categorias',
            'descripcion' => 'nullable|string'
        ]);

        $categoria = Categoria::create($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Categoría creada exitosamente',
            'data' => $categoria
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/categorias/{categoria}",
     *     summary="Obtener detalles de una categoría",
     *     tags={"Categorías"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="categoria",
     *         in="path",
     *         required=true,
     *         description="ID de la categoría",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detalles de la categoría obtenidos exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="descripcion", type="string"),
     *                 @OA\Property(property="is_activo", type="boolean")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Categoría no encontrada"
     *     )
     * )
     */
    public function show(Categoria $categoria)
    {
        return response()->json([
            'status' => 'success',
            'data' => $categoria
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/categorias/{categoria}",
     *     summary="Actualizar una categoría",
     *     tags={"Categorías"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="categoria",
     *         in="path",
     *         required=true,
     *         description="ID de la categoría",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", maxLength=100),
     *             @OA\Property(property="descripcion", type="string"),
     *             @OA\Property(property="is_activo", type="boolean")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Categoría actualizada exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="descripcion", type="string"),
     *                 @OA\Property(property="is_activo", type="boolean")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Categoría no encontrada"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación"
     *     )
     * )
     */
    public function update(Request $request, Categoria $categoria)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('categorias')->ignore($categoria)],
            'descripcion' => 'nullable|string',
            'is_activo' => 'boolean'
        ]);

        $categoria->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Categoría actualizada exitosamente',
            'data' => $categoria
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/categorias/{categoria}",
     *     summary="Eliminar una categoría",
     *     tags={"Categorías"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="categoria",
     *         in="path",
     *         required=true,
     *         description="ID de la categoría",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Categoría eliminada exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Categoría no encontrada"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="No se puede eliminar la categoría"
     *     )
     * )
     */
    public function destroy(Categoria $categoria)
    {
        if ($categoria->productos()->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No se puede eliminar la categoría porque está siendo utilizada por productos'
            ], 422);
        }

        $categoria->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Categoría eliminada exitosamente'
        ]);
    }
} 