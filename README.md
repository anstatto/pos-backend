# 🏪 Sistema POS Backend

## 📝 Descripción
Sistema de Punto de Venta (POS) desarrollado con Laravel 12, enfocado en la gestión de ventas, compras, inventario y facturación fiscal para la República Dominicana.

## ⭐ Características Principales

### 🛍️ Gestión de Ventas
- Registro de ventas con NCF (Números de Comprobantes Fiscales)
- Múltiples tipos de comprobantes (Facturas, Notas de Crédito, Notas de Débito)
- Control de ventas a crédito y contado
- Gestión de cuentas por cobrar
- Anulación de ventas con reversión automática de inventario

### 🛒 Gestión de Compras
- Registro de compras con validación de NCF
- Control de compras a crédito y contado
- Gestión de cuentas por pagar
- Actualización automática de costos de productos
- Registro histórico de cambios de precios

### 📦 Control de Inventario
- Gestión de productos y categorías
- Múltiples unidades de medida
- Ajustes de inventario (entradas/salidas)
- Historial de movimientos
- Control de stock mínimo

### 🧾 Facturación Fiscal (NCF)
- Generación automática de secuencias NCF
- Tipos soportados:
  - B01: Facturas de Crédito Fiscal
  - B02: Facturas de Consumo
  - B03: Notas de Débito
  - B04: Notas de Crédito
- Control de vencimiento de secuencias
- Validación de formato NCF

### 🔒 Seguridad
- Autenticación mediante Laravel Sanctum
- Control de acceso basado en roles y permisos
- Registro de actividades del usuario
- Protección contra duplicación de comprobantes

## 💻 Requisitos Técnicos

### 🛠️ Requisitos del Sistema
- PHP >= 8.2
- MySQL >= 8.0
- Composer
- Node.js y NPM (para assets)

### 📚 Dependencias Principales
- Laravel 12.x
- Laravel Sanctum (Autenticación)
- Swagger PHP (Documentación API)

## 🚀 Instalación

1. Clonar el repositorio:
```bash
git clone [url-del-repositorio]
```

2. Instalar dependencias:
```bash
composer install
```

3. Configurar el archivo .env:
```bash
cp .env.example .env
php artisan key:generate

```

4. Configurar la base de datos en .env:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nombre_db
DB_USERNAME=usuario
DB_PASSWORD=contraseña
```

5. Ejecutar migraciones, Documentacion y seeders :
```bash
php artisan l5-swagger:generate
php artisan migrate --seed
```

## 🌐 Estructura de la API

### 🔑 Autenticación
- `POST /api/login` - Iniciar sesión
- `POST /api/logout` - Cerrar sesión
- `GET /api/user` - Obtener usuario actual
- `GET /api/me` - Obtener usuario actual (alias)
- `GET /api/users` - Listar usuarios (admin)

### 👥 Roles y Permisos
- `GET /api/roles` - Listar roles
- `POST /api/roles` - Crear rol
- `GET /api/roles/{id}` - Ver rol
- `PUT /api/roles/{id}` - Actualizar rol
- `DELETE /api/roles/{id}` - Eliminar rol
- `GET /api/permisos` - Listar permisos
- `POST /api/permisos` - Crear permiso
- `GET /api/permisos/{id}` - Ver permiso
- `PUT /api/permisos/{id}` - Actualizar permiso
- `DELETE /api/permisos/{id}` - Eliminar permiso

### 🛍️ Ventas
- `GET /api/ventas` - Listar ventas
- `POST /api/ventas` - Crear venta
- `GET /api/ventas/{id}` - Ver detalle
- `DELETE /api/ventas/{id}` - Anular venta

### 🛒 Compras
- `GET /api/compras` - Listar compras
- `POST /api/compras` - Crear compra
- `GET /api/compras/{id}` - Ver detalle
- `DELETE /api/compras/{id}` - Anular compra

### 📦 Productos y Categorías
- `GET /api/productos` - Listar productos
- `POST /api/productos` - Crear producto
- `GET /api/productos/{id}` - Ver producto
- `PUT /api/productos/{id}` - Actualizar producto
- `DELETE /api/productos/{id}` - Eliminar producto
- `GET /api/categorias` - Listar categorías
- `POST /api/categorias` - Crear categoría
- `GET /api/categorias/{id}` - Ver categoría
- `PUT /api/categorias/{id}` - Actualizar categoría
- `DELETE /api/categorias/{id}` - Eliminar categoría

### 📏 Unidades de Medida
- `GET /api/unidades-medida` - Listar unidades
- `POST /api/unidades-medida` - Crear unidad
- `GET /api/unidades-medida/{id}` - Ver unidad
- `PUT /api/unidades-medida/{id}` - Actualizar unidad
- `DELETE /api/unidades-medida/{id}` - Eliminar unidad

### 💰 Condiciones de Pago
- `GET /api/condiciones-pago` - Listar condiciones
- `POST /api/condiciones-pago` - Crear condición
- `GET /api/condiciones-pago/{id}` - Ver condición
- `PUT /api/condiciones-pago/{id}` - Actualizar condición
- `DELETE /api/condiciones-pago/{id}` - Eliminar condición

### 👥 Clientes y Proveedores
- `GET /api/clientes` - Listar clientes
- `POST /api/clientes` - Crear cliente
- `GET /api/clientes/{id}` - Ver cliente
- `PUT /api/clientes/{id}` - Actualizar cliente
- `DELETE /api/clientes/{id}` - Eliminar cliente
- `GET /api/proveedores` - Listar proveedores
- `POST /api/proveedores` - Crear proveedor
- `GET /api/proveedores/{id}` - Ver proveedor
- `PUT /api/proveedores/{id}` - Actualizar proveedor
- `DELETE /api/proveedores/{id}` - Eliminar proveedor

### 💵 Pagos
- `GET /api/pagos` - Listar pagos
- `POST /api/pagos` - Crear pago
- `GET /api/pagos/{id}` - Ver pago
- `PUT /api/pagos/{id}` - Actualizar pago
- `DELETE /api/pagos/{id}` - Eliminar pago

### 📊 Ajustes de Inventario
- `GET /api/ajustes-inventario` - Listar ajustes
- `POST /api/ajustes-inventario` - Crear ajuste
- `GET /api/ajustes-inventario/{id}` - Ver detalle
- `POST /api/ajustes-inventario/{id}/completar` - Completar ajuste
- `POST /api/ajustes-inventario/{id}/anular` - Anular ajuste

### 📋 Reportes y Auditoría
- `GET /api/reportes/{tipo}` - Generar reporte
- `GET /api/auditoria` - Ver registro de actividades

## 📖 Documentación API

La documentación completa de la API está disponible en formato Swagger/OpenAPI:

```bash
# Generar documentación
php artisan l5-swagger:generate

# Acceder a la documentación
http://localhost:8000/api/documentation
```

## 🛠️ Desarrollo

### ⌨️ Comandos Útiles
```bash
# Ejecutar pruebas
php artisan test

# Generar documentación API
php artisan l5-swagger:generate

# Iniciar servidor de desarrollo
php artisan serve
```

### 📝 Convenciones de Código
- PSR-12 para estilo de código
- Nombres de clases en PascalCase
- Nombres de métodos en camelCase
- Nombres de variables en snake_case
- Documentación de métodos con PHPDoc

## 🤝 Contribución
1. Fork el repositorio
2. Crea una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abre un Pull Request

## 📄 Licencia
Este proyecto está bajo la Licencia MIT - ver el archivo [LICENSE](LICENSE) para más detalles.

## 💬 Soporte
Para soporte y consultas, por favor crear un issue en el repositorio o contactar al equipo de desarrollo.
