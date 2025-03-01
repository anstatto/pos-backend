<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permiso;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * @OA\Tag(
 *     name="Permisos",
 *     description="API Endpoints de permisos del sistema"
 * )
 */
class PermisoController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/permisos",
     *     summary="Listar todos los permisos",
     *     tags={"Permisos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Lista de permisos obtenida exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="name", type="string"),
     *                     @OA\Property(property="descripcion", type="string"),
     *                     @OA\Property(property="grupo", type="string"),
     *                     @OA\Property(property="is_activo", type="boolean")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function index()
    {
        $permisos = Permiso::where('is_activo', true)
            ->orderBy('grupo')
            ->orderBy('name')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $permisos
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/permisos",
     *     summary="Crear un nuevo permiso",
     *     tags={"Permisos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", maxLength=50),
     *             @OA\Property(property="descripcion", type="string"),
     *             @OA\Property(property="grupo", type="string", maxLength=50)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Permiso creado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="descripcion", type="string"),
     *                 @OA\Property(property="grupo", type="string"),
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
            'name' => 'required|string|max:50|unique:permisos',
            'descripcion' => 'nullable|string',
            'grupo' => 'nullable|string|max:50'
        ]);

        $permiso = Permiso::create($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Permiso creado exitosamente',
            'data' => $permiso
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/permisos/{permiso}",
     *     summary="Obtener detalles de un permiso",
     *     tags={"Permisos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="permiso",
     *         in="path",
     *         required=true,
     *         description="ID del permiso",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detalles del permiso obtenidos exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="descripcion", type="string"),
     *                 @OA\Property(property="grupo", type="string"),
     *                 @OA\Property(property="is_activo", type="boolean"),
     *                 @OA\Property(property="roles", type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="name", type="string")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Permiso no encontrado"
     *     )
     * )
     */
    public function show(Permiso $permiso)
    {
        return response()->json([
            'status' => 'success',
            'data' => $permiso->load('roles')
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/permisos/{permiso}",
     *     summary="Actualizar un permiso",
     *     tags={"Permisos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="permiso",
     *         in="path",
     *         required=true,
     *         description="ID del permiso",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", maxLength=50),
     *             @OA\Property(property="descripcion", type="string"),
     *             @OA\Property(property="grupo", type="string", maxLength=50),
     *             @OA\Property(property="is_activo", type="boolean")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Permiso actualizado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="descripcion", type="string"),
     *                 @OA\Property(property="grupo", type="string"),
     *                 @OA\Property(property="is_activo", type="boolean")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Permiso no encontrado"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación"
     *     )
     * )
     */
    public function update(Request $request, Permiso $permiso)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:50', Rule::unique('permisos')->ignore($permiso)],
            'descripcion' => 'nullable|string',
            'grupo' => 'nullable|string|max:50',
            'is_activo' => 'boolean'
        ]);

        $permiso->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Permiso actualizado exitosamente',
            'data' => $permiso
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/permisos/{permiso}",
     *     summary="Eliminar un permiso",
     *     tags={"Permisos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="permiso",
     *         in="path",
     *         required=true,
     *         description="ID del permiso",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Permiso eliminado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Permiso no encontrado"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="No se puede eliminar el permiso"
     *     )
     * )
     */
    public function destroy(Permiso $permiso)
    {
        if ($permiso->roles()->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No se puede eliminar el permiso porque está siendo utilizado por roles'
            ], 422);
        }

        $permiso->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Permiso eliminado exitosamente'
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/permisos/{permiso}/assign-roles",
     *     summary="Asignar roles a un permiso",
     *     tags={"Permisos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="permiso",
     *         in="path",
     *         required=true,
     *         description="ID del permiso",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"roles"},
     *             @OA\Property(property="roles", type="array",
     *                 @OA\Items(type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Roles asignados exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="descripcion", type="string"),
     *                 @OA\Property(property="grupo", type="string"),
     *                 @OA\Property(property="is_activo", type="boolean"),
     *                 @OA\Property(property="roles", type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="name", type="string")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Permiso no encontrado"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación"
     *     )
     * )
     */
    public function assignRoles(Request $request, Permiso $permiso)
    {
        $request->validate([
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,id'
        ]);

        $permiso->roles()->sync($request->roles);

        return response()->json([
            'status' => 'success',
            'message' => 'Roles asignados exitosamente',
            'data' => $permiso->load('roles')
        ]);
    }
} 