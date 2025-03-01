<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\VentaController;
use App\Http\Controllers\Api\CompraController;
use App\Http\Controllers\Api\ProductoController;
use App\Http\Controllers\Api\ClienteController;
use App\Http\Controllers\Api\ProveedorController;
use App\Http\Controllers\Api\CategoriaController;
use App\Http\Controllers\Api\PagoController;
use App\Http\Controllers\Api\ReporteController;
use App\Http\Controllers\Api\AuditoriaController;
use App\Http\Controllers\Api\AjusteInventarioController;
use App\Http\Controllers\Api\PermisoController;
use App\Http\Controllers\Api\CondicionPagoController;
use App\Http\Controllers\Api\UnidadMedidaController;


/*
|-----------------------------------------------    ---------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Rutas de autenticación
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::get('/me', function (Request $request) {
        return $request->user();
    });
    Route::post('/logout', [AuthController::class, 'logout']);

    // Rutas para administradores
    Route::middleware(['role:Administrador'])->group(function () {
        Route::apiResource('roles', RoleController::class);
        Route::get('/users', [AuthController::class, 'users']);
        Route::get('/auditoria', [AuditoriaController::class, 'index']);
        Route::get('/reportes/{tipo}', [ReporteController::class, 'generate']);
        Route::apiResource('permisos', PermisoController::class);
    });

    // Rutas para ventas
    Route::middleware(['role:Vendedor|Administrador'])->prefix('ventas')->group(function () {
        Route::get('/', [VentaController::class, 'index'])->middleware('permission:ver_ventas');
        Route::post('/', [VentaController::class, 'store'])->middleware('permission:crear_ventas');
        Route::delete('/{venta}', [VentaController::class, 'destroy'])->middleware('permission:anular_ventas');
    });

    // Rutas para compras
    Route::middleware(['role:Almacenista|Administrador'])->prefix('compras')->group(function () {
        Route::get('/', [CompraController::class, 'index'])->middleware('permission:ver_compras');
        Route::post('/', [CompraController::class, 'store'])->middleware('permission:crear_compras');
        Route::delete('/{compra}', [CompraController::class, 'destroy'])->middleware('permission:anular_compras');
    });

    // Rutas para productos
    Route::apiResource('productos', ProductoController::class)->middleware('permission:ver_productos');
    Route::apiResource('categorias', CategoriaController::class);

    // Rutas para clientes y proveedores
    Route::apiResource('clientes', ClienteController::class);
    Route::apiResource('proveedores', ProveedorController::class);

    // Rutas para pagos
    Route::apiResource('pagos', PagoController::class);

    // Rutas de ajustes de inventario
    Route::get('/ajustes-inventario', [AjusteInventarioController::class, 'index'])
        ->middleware('permission:ver_ajustes_inventario');
    Route::post('/ajustes-inventario', [AjusteInventarioController::class, 'store'])
        ->middleware('permission:crear_ajustes_inventario');
    Route::get('/ajustes-inventario/{id}', [AjusteInventarioController::class, 'show'])
        ->middleware('permission:ver_ajustes_inventario');
    Route::post('/ajustes-inventario/{id}/completar', [AjusteInventarioController::class, 'completar'])
        ->middleware('permission:completar_ajustes_inventario');
    Route::post('/ajustes-inventario/{id}/anular', [AjusteInventarioController::class, 'anular'])
        ->middleware('permission:anular_ajustes_inventario');

    // Rutas para condiciones de pago
    Route::apiResource('condiciones-pago', CondicionPagoController::class);

    // Rutas para unidades de medida
    Route::apiResource('unidades-medida', UnidadMedidaController::class);
});
