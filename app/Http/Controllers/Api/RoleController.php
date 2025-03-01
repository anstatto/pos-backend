<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * @OA\Tag(
 *     name="Roles",
 *     description="API Endpoints de roles y gestión de permisos"
 * )
 */
class RoleController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/roles",
     *     summary="Listar todos los roles",
     *     tags={"Roles"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Lista de roles obtenida exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="name", type="string"),
     *                     @OA\Property(property="descripcion", type="string"),
     *                     @OA\Property(property="is_activo", type="boolean"),
     *                     @OA\Property(property="permisos", type="array",
     *                         @OA\Items(
     *                             @OA\Property(property="id", type="integer"),
     *                             @OA\Property(property="name", type="string")
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function index()
    {
        $roles = Role::with('permisos')
            ->where('is_activo', true)
            ->orderBy('name')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $roles
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/roles",
     *     summary="Crear un nuevo rol",
     *     tags={"Roles"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", maxLength=50),
     *             @OA\Property(property="descripcion", type="string"),
     *             @OA\Property(property="permisos", type="array",
     *                 @OA\Items(type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Rol creado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="descripcion", type="string"),
     *                 @OA\Property(property="is_activo", type="boolean"),
     *                 @OA\Property(property="permisos", type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="name", type="string")
     *                     )
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
            'name' => 'required|string|max:50|unique:roles',
            'descripcion' => 'nullable|string',
            'permisos' => 'nullable|array',
            'permisos.*' => 'exists:permisos,id'
        ]);

        $role = Role::create($request->only(['name', 'descripcion']));

        if ($request->has('permisos')) {
            $role->permisos()->sync($request->permisos);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Rol creado exitosamente',
            'data' => $role->load('permisos')
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/roles/{role}",
     *     summary="Obtener detalles de un rol",
     *     tags={"Roles"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="role",
     *         in="path",
     *         required=true,
     *         description="ID del rol",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detalles del rol obtenidos exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="descripcion", type="string"),
     *                 @OA\Property(property="is_activo", type="boolean"),
     *                 @OA\Property(property="permisos", type="array",
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
     *         description="Rol no encontrado"
     *     )
     * )
     */
    public function show(Role $role)
    {
        return response()->json([
            'status' => 'success',
            'data' => $role->load('permisos')
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/roles/{role}",
     *     summary="Actualizar un rol",
     *     tags={"Roles"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="role",
     *         in="path",
     *         required=true,
     *         description="ID del rol",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", maxLength=50),
     *             @OA\Property(property="descripcion", type="string"),
     *             @OA\Property(property="permisos", type="array",
     *                 @OA\Items(type="integer")
     *             ),
     *             @OA\Property(property="is_activo", type="boolean")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Rol actualizado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="descripcion", type="string"),
     *                 @OA\Property(property="is_activo", type="boolean"),
     *                 @OA\Property(property="permisos", type="array",
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
     *         description="Rol no encontrado"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación"
     *     )
     * )
     */
    public function update(Request $request, Role $role)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:50', Rule::unique('roles')->ignore($role)],
            'descripcion' => 'nullable|string',
            'permisos' => 'nullable|array',
            'permisos.*' => 'exists:permisos,id',
            'is_activo' => 'boolean'
        ]);

        $role->update($request->only(['name', 'descripcion', 'is_activo']));

        if ($request->has('permisos')) {
            $role->permisos()->sync($request->permisos);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Rol actualizado exitosamente',
            'data' => $role->load('permisos')
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/roles/{role}",
     *     summary="Eliminar un rol",
     *     tags={"Roles"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="role",
     *         in="path",
     *         required=true,
     *         description="ID del rol",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Rol eliminado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Rol no encontrado"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="No se puede eliminar el rol"
     *     )
     * )
     */
    public function destroy(Role $role)
    {
        if ($role->users()->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No se puede eliminar el rol porque está siendo utilizado por usuarios'
            ], 422);
        }

        $role->permisos()->detach();
        $role->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Rol eliminado exitosamente'
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/roles/{role}/assign-permissions",
     *     summary="Asignar permisos a un rol",
     *     tags={"Roles"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="role",
     *         in="path",
     *         required=true,
     *         description="ID del rol",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"permisos"},
     *             @OA\Property(property="permisos", type="array",
     *                 @OA\Items(type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Permisos asignados exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="descripcion", type="string"),
     *                 @OA\Property(property="is_activo", type="boolean"),
     *                 @OA\Property(property="permisos", type="array",
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
     *         description="Rol no encontrado"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación"
     *     )
     * )
     */
    public function assignPermissions(Request $request, Role $role)
    {
        $request->validate([
            'permisos' => 'required|array',
            'permisos.*' => 'exists:permisos,id'
        ]);

        $role->permisos()->sync($request->permisos);

        return response()->json([
            'status' => 'success',
            'message' => 'Permisos asignados exitosamente',
            'data' => $role->load('permisos')
        ]);
    }
} 