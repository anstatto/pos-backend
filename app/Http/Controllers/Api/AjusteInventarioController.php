<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AjusteInventario;
use App\Models\AjusteInventarioDetalle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Ajustes de Inventario",
 *     description="API Endpoints de ajustes de inventario"
 * )
 */
class AjusteInventarioController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/ajustes-inventario",
     *     summary="Obtener lista de ajustes de inventario",
     *     tags={"Ajustes de Inventario"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="tipo",
     *         in="query",
     *         description="Filtrar por tipo de ajuste (ENTRADA/SALIDA)",
     *         required=false,
     *         @OA\Schema(type="string", enum={"ENTRADA", "SALIDA"})
     *     ),
     *     @OA\Parameter(
     *         name="estado",
     *         in="query",
     *         description="Filtrar por estado del ajuste",
     *         required=false,
     *         @OA\Schema(type="string", enum={"PENDIENTE", "COMPLETADO", "ANULADO"})
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Cantidad de registros por página",
     *         required=false,
     *         @OA\Schema(type="integer", default=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de ajustes de inventario",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="numero", type="string"),
     *                     @OA\Property(property="tipo", type="string"),
     *                     @OA\Property(property="motivo", type="string"),
     *                     @OA\Property(property="estado", type="string"),
     *                     @OA\Property(property="created_at", type="string", format="date-time")
     *                 )
     *             ),
     *             @OA\Property(property="current_page", type="integer"),
     *             @OA\Property(property="total", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autorizado"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Prohibido - No tiene permiso para ver ajustes"
     *     )
     * )
     */
    public function index(Request $request)
    {
        $query = AjusteInventario::with(['user', 'detalles.producto', 'detalles.unidadMedida'])
            ->orderBy('created_at', 'desc');

        if ($request->has('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        return $query->paginate($request->get('per_page', 10));
    }

    /**
     * @OA\Post(
     *     path="/api/ajustes-inventario",
     *     summary="Crear un nuevo ajuste de inventario",
     *     tags={"Ajustes de Inventario"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"tipo","motivo","detalles"},
     *             @OA\Property(property="tipo", type="string", enum={"ENTRADA","SALIDA"}),
     *             @OA\Property(property="motivo", type="string", description="Motivo del ajuste"),
     *             @OA\Property(property="nota", type="string", nullable=true),
     *             @OA\Property(property="detalles", type="array",
     *                 @OA\Items(
     *                     required={"producto_id","unidad_medida_id","cantidad","costo"},
     *                     @OA\Property(property="producto_id", type="integer"),
     *                     @OA\Property(property="unidad_medida_id", type="integer"),
     *                     @OA\Property(property="cantidad", type="number", format="float"),
     *                     @OA\Property(property="costo", type="number", format="float"),
     *                     @OA\Property(property="nota", type="string", nullable=true)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Ajuste de inventario creado",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="numero", type="string"),
     *             @OA\Property(property="tipo", type="string"),
     *             @OA\Property(property="motivo", type="string"),
     *             @OA\Property(property="estado", type="string"),
     *             @OA\Property(property="detalles", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="producto", type="object"),
     *                     @OA\Property(property="cantidad", type="number"),
     *                     @OA\Property(property="costo", type="number")
     *                 )
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
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autorizado"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Prohibido - No tiene permiso para crear ajustes"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'tipo' => 'required|in:ENTRADA,SALIDA',
            'motivo' => 'required|string|max:255',
            'nota' => 'nullable|string',
            'detalles' => 'required|array|min:1',
            'detalles.*.producto_id' => 'required|exists:productos,id',
            'detalles.*.unidad_medida_id' => 'required|exists:unidad_medidas,id',
            'detalles.*.cantidad' => 'required|numeric|min:0.01',
            'detalles.*.costo' => 'required|numeric|min:0',
            'detalles.*.nota' => 'nullable|string'
        ]);

        try {
            DB::beginTransaction();

            $ajuste = AjusteInventario::create([
                'numero' => 'AJ-' . date('Ymd') . '-' . str_pad(AjusteInventario::count() + 1, 4, '0', STR_PAD_LEFT),
                'user_id' => $request->user()->id,
                'tipo' => $request->tipo,
                'motivo' => $request->motivo,
                'nota' => $request->nota,
                'estado' => 'PENDIENTE'
            ]);

            foreach ($request->detalles as $detalle) {
                $ajuste->detalles()->create($detalle);
            }

            DB::commit();
            return response()->json($ajuste->load(['detalles.producto', 'detalles.unidadMedida']), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al crear el ajuste: ' . $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/ajustes-inventario/{id}",
     *     summary="Obtener detalle de un ajuste de inventario",
     *     tags={"Ajustes de Inventario"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detalle del ajuste de inventario",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="numero", type="string"),
     *             @OA\Property(property="tipo", type="string"),
     *             @OA\Property(property="motivo", type="string"),
     *             @OA\Property(property="estado", type="string"),
     *             @OA\Property(property="user", type="object"),
     *             @OA\Property(property="detalles", type="array",
     *                 @OA\Items(type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Ajuste no encontrado"
     *     )
     * )
     */
    public function show($id)
    {
        return AjusteInventario::with(['user', 'detalles.producto', 'detalles.unidadMedida'])
            ->findOrFail($id);
    }

    /**
     * @OA\Post(
     *     path="/api/ajustes-inventario/{id}/completar",
     *     summary="Completar un ajuste de inventario",
     *     tags={"Ajustes de Inventario"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Ajuste completado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Error al completar el ajuste",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Ajuste no encontrado"
     *     )
     * )
     */
    public function completar($id)
    {
        try {
            $ajuste = AjusteInventario::findOrFail($id);
            $ajuste->completar();
            return response()->json(['message' => 'Ajuste completado exitosamente']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/ajustes-inventario/{id}/anular",
     *     summary="Anular un ajuste de inventario",
     *     tags={"Ajustes de Inventario"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Ajuste anulado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Error al anular el ajuste",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Ajuste no encontrado"
     *     )
     * )
     */
    public function anular($id)
    {
        try {
            $ajuste = AjusteInventario::findOrFail($id);
            $ajuste->anular();
            return response()->json(['message' => 'Ajuste anulado exitosamente']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
} 