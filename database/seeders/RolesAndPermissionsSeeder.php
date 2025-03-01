<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permiso;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        // Crear permisos por módulo
        $permisos = [
            // Módulo de usuarios
            ['name' => 'ver_usuarios', 'descripcion' => 'Ver listado de usuarios', 'grupo' => 'Usuarios'],
            ['name' => 'crear_usuarios', 'descripcion' => 'Crear nuevos usuarios', 'grupo' => 'Usuarios'],
            ['name' => 'editar_usuarios', 'descripcion' => 'Editar usuarios existentes', 'grupo' => 'Usuarios'],
            ['name' => 'eliminar_usuarios', 'descripcion' => 'Eliminar usuarios', 'grupo' => 'Usuarios'],

            // Módulo de roles
            ['name' => 'ver_roles', 'descripcion' => 'Ver listado de roles', 'grupo' => 'Roles'],
            ['name' => 'crear_roles', 'descripcion' => 'Crear nuevos roles', 'grupo' => 'Roles'],
            ['name' => 'editar_roles', 'descripcion' => 'Editar roles existentes', 'grupo' => 'Roles'],
            ['name' => 'eliminar_roles', 'descripcion' => 'Eliminar roles', 'grupo' => 'Roles'],

            // Módulo de productos
            ['name' => 'ver_productos', 'descripcion' => 'Ver listado de productos', 'grupo' => 'Productos'],
            ['name' => 'crear_productos', 'descripcion' => 'Crear nuevos productos', 'grupo' => 'Productos'],
            ['name' => 'editar_productos', 'descripcion' => 'Editar productos existentes', 'grupo' => 'Productos'],
            ['name' => 'eliminar_productos', 'descripcion' => 'Eliminar productos', 'grupo' => 'Productos'],
            ['name' => 'ajustar_inventario', 'descripcion' => 'Realizar ajustes de inventario', 'grupo' => 'Productos'],

            // Módulo de ventas
            ['name' => 'ver_ventas', 'descripcion' => 'Ver listado de ventas', 'grupo' => 'Ventas'],
            ['name' => 'crear_ventas', 'descripcion' => 'Crear nuevas ventas', 'grupo' => 'Ventas'],
            ['name' => 'anular_ventas', 'descripcion' => 'Anular ventas', 'grupo' => 'Ventas'],

            // Módulo de compras
            ['name' => 'ver_compras', 'descripcion' => 'Ver listado de compras', 'grupo' => 'Compras'],
            ['name' => 'crear_compras', 'descripcion' => 'Crear nuevas compras', 'grupo' => 'Compras'],
            ['name' => 'anular_compras', 'descripcion' => 'Anular compras', 'grupo' => 'Compras'],

            // Módulo de clientes
            ['name' => 'ver_clientes', 'descripcion' => 'Ver listado de clientes', 'grupo' => 'Clientes'],
            ['name' => 'crear_clientes', 'descripcion' => 'Crear nuevos clientes', 'grupo' => 'Clientes'],
            ['name' => 'editar_clientes', 'descripcion' => 'Editar clientes existentes', 'grupo' => 'Clientes'],
            ['name' => 'eliminar_clientes', 'descripcion' => 'Eliminar clientes', 'grupo' => 'Clientes'],

            // Módulo de proveedores
            ['name' => 'ver_proveedores', 'descripcion' => 'Ver listado de proveedores', 'grupo' => 'Proveedores'],
            ['name' => 'crear_proveedores', 'descripcion' => 'Crear nuevos proveedores', 'grupo' => 'Proveedores'],
            ['name' => 'editar_proveedores', 'descripcion' => 'Editar proveedores existentes', 'grupo' => 'Proveedores'],
            ['name' => 'eliminar_proveedores', 'descripcion' => 'Eliminar proveedores', 'grupo' => 'Proveedores'],

            // Módulo de pagos
            ['name' => 'ver_pagos', 'descripcion' => 'Ver listado de pagos', 'grupo' => 'Pagos'],
            ['name' => 'registrar_pagos', 'descripcion' => 'Registrar nuevos pagos', 'grupo' => 'Pagos'],
            ['name' => 'anular_pagos', 'descripcion' => 'Anular pagos', 'grupo' => 'Pagos'],

            // Módulo de caja
            ['name' => 'abrir_caja', 'descripcion' => 'Abrir caja', 'grupo' => 'Caja'],
            ['name' => 'cerrar_caja', 'descripcion' => 'Cerrar caja', 'grupo' => 'Caja'],
            ['name' => 'ver_estado_caja', 'descripcion' => 'Ver estado de caja', 'grupo' => 'Caja'],

            // Módulo de reportes
            ['name' => 'ver_reportes_ventas', 'descripcion' => 'Ver reportes de ventas', 'grupo' => 'Reportes'],
            ['name' => 'ver_reportes_compras', 'descripcion' => 'Ver reportes de compras', 'grupo' => 'Reportes'],
            ['name' => 'ver_reportes_inventario', 'descripcion' => 'Ver reportes de inventario', 'grupo' => 'Reportes'],
            ['name' => 'ver_reportes_cuentas', 'descripcion' => 'Ver reportes de cuentas', 'grupo' => 'Reportes'],

            // Módulo de configuración
            ['name' => 'ver_configuracion', 'descripcion' => 'Ver configuración del sistema', 'grupo' => 'Configuración'],
            ['name' => 'editar_configuracion', 'descripcion' => 'Editar configuración del sistema', 'grupo' => 'Configuración'],

            // Permisos de ajustes de inventario
            [
                'name' => 'ver_ajustes_inventario',
                'descripcion' => 'Ver ajustes de inventario',
                'grupo' => 'ajustes_inventario'
            ],
            [
                'name' => 'crear_ajustes_inventario',
                'descripcion' => 'Crear ajustes de inventario',
                'grupo' => 'ajustes_inventario'
            ],
            [
                'name' => 'completar_ajustes_inventario',
                'descripcion' => 'Completar ajustes de inventario',
                'grupo' => 'ajustes_inventario'
            ],
            [
                'name' => 'anular_ajustes_inventario',
                'descripcion' => 'Anular ajustes de inventario',
                'grupo' => 'ajustes_inventario'
            ]
        ];

        // Crear los permisos
        foreach ($permisos as $permiso) {
            Permiso::create($permiso);
        }

        // Crear roles predefinidos
        $roles = [
            [
                'name' => 'Administrador',
                'descripcion' => 'Acceso completo al sistema'
            ],
            [
                'name' => 'Vendedor',
                'descripcion' => 'Gestión de ventas y clientes'
            ],
            [
                'name' => 'Almacenista',
                'descripcion' => 'Gestión de inventario y compras'
            ],
            [
                'name' => 'Cajero',
                'descripcion' => 'Gestión de caja y pagos'
            ]
        ];

        foreach ($roles as $rol) {
            Role::create($rol);
        }

        // Asignar permisos a roles
        $admin = Role::where('name', 'Administrador')->first();
        $admin->permisos()->attach(Permiso::all());

        $vendedor = Role::where('name', 'Vendedor')->first();
        $vendedor->permisos()->attach(
            Permiso::whereIn('name', [
                'ver_productos', 'ver_clientes', 'crear_clientes', 'editar_clientes',
                'ver_ventas', 'crear_ventas', 'ver_pagos', 'registrar_pagos',
                'abrir_caja', 'cerrar_caja', 'ver_estado_caja',
                'ver_reportes_ventas'
            ])->get()
        );

        $almacenista = Role::where('name', 'Almacenista')->first();
        $almacenista->permisos()->attach(
            Permiso::whereIn('name', [
                'ver_productos', 'crear_productos', 'editar_productos', 'ajustar_inventario',
                'ver_proveedores', 'crear_proveedores', 'editar_proveedores',
                'ver_compras', 'crear_compras',
                'ver_reportes_compras', 'ver_reportes_inventario'
            ])->get()
        );

        $cajero = Role::where('name', 'Cajero')->first();
        $cajero->permisos()->attach(
            Permiso::whereIn('name', [
                'ver_pagos', 'registrar_pagos',
                'abrir_caja', 'cerrar_caja', 'ver_estado_caja',
                'ver_reportes_cuentas'
            ])->get()
        );
    }
} 