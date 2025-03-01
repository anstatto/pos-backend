<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Primero creamos las tablas base sin dependencias
        Schema::create('proveedores', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('email')->unique()->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('rnc', 20)->unique()->nullable();
            $table->text('direccion')->nullable();
            $table->string('ciudad', 50)->nullable();
            $table->string('provincia', 50)->nullable();
            $table->string('contacto_nombre', 100)->nullable();
            $table->string('contacto_telefono', 20)->nullable();
            $table->decimal('balance', 12, 2)->default(0);
            $table->boolean('is_activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('email')->unique()->nullable();
            $table->string('phone', 20)->nullable();
            $table->foreignId('condicion_pago_id')->constrained('condicion_pagos');
            $table->enum('tipo_identificacion', ['CEDULA', 'RNC', 'PASAPORTE'])->nullable();
            $table->string('identificacion', 20)->unique()->nullable();
            $table->enum('tipo_contribuyente', ['PERSONA_FISICA', 'PERSONA_JURIDICA'])->nullable();
            $table->text('direccion')->nullable();
            $table->string('ciudad', 50)->nullable();
            $table->string('provincia', 50)->nullable();
            $table->decimal('limite_credito', 12, 2)->default(0);
            $table->boolean('is_activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. Luego las tablas que dependen de clientes y proveedores
        Schema::create('direcciones', function (Blueprint $table) {
            $table->id();
            $table->string('calle');
            $table->string('ciudad');
            $table->string('estado');
            $table->string('pais');
            $table->string('codigo_postal');
            $table->foreignId('cliente_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('proveedor_id')->nullable()->constrained('proveedores')->onDelete('cascade');
            $table->boolean('is_activo')->default(true);
            $table->timestamps();
        });

        // 3. Tablas de productos e inventario
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 20)->unique();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->decimal('precio_venta', 10, 2);
            $table->decimal('precio_compra', 10, 2);
            $table->integer('stock')->default(0);
            $table->integer('stock_minimo')->default(5);
            $table->decimal('impuesto', 5, 2)->default(18.00);
            $table->decimal('descuento', 5, 2)->default(0);
            $table->foreignId('categoria_id')->nullable()->constrained('categorias');
            $table->foreignId('unidad_medida_id')->constrained('unidad_medidas');
            $table->boolean('stock_bajo')->default(false);
            $table->string('codigo_barra')->nullable()->unique();
            $table->integer('tipo_itbis')->default(1);
            $table->boolean('is_activo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['codigo', 'codigo_barra']);
        });

        Schema::create('detalle_unidad_productos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained()->onDelete('cascade');
            $table->foreignId('unidad_medida_id')->constrained('unidad_medidas');
            $table->decimal('precio_venta', 10, 2);
            $table->decimal('precio_compra', 10, 2);
            $table->integer('contenido')->unsigned();
            $table->boolean('is_activo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['producto_id', 'unidad_medida_id']);
        });

        Schema::create('movimiento_inventarios', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo', ['ENTRADA', 'SALIDA', 'AJUSTE', 'DEVOLUCION']);
            $table->integer('cantidad')->default(0);
            $table->foreignId('producto_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->string('referencia')->nullable();
            $table->text('nota')->nullable();
            $table->decimal('costo', 10, 2)->nullable();
            $table->boolean('is_activo')->default(true);
            $table->timestamp('fecha')->useCurrent();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['producto_id', 'fecha']);
        });

        Schema::create('historial_costos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained();
            $table->decimal('precio_costo', 10, 2);
            $table->timestamp('fecha')->useCurrent();
            $table->timestamps();
        });

        // 4. Tablas de ventas y compras
        Schema::create('ventas', function (Blueprint $table) {
            $table->id();
            $table->string('numero')->unique();
            $table->foreignId('cliente_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->date('fecha');
            $table->string('tipo_comprobante', 20)->comment('01-FACTURA, 02-NOTA_CREDITO, 03-NOTA_DEBITO');
            $table->string('ncf', 19)->unique();
            $table->decimal('subtotal', 12, 2);
            $table->decimal('impuesto', 12, 2);
            $table->decimal('descuento', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->enum('estado', ['PENDIENTE', 'COMPLETADA', 'ANULADA'])->default('PENDIENTE');
            $table->text('observacion')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('venta_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')->constrained()->onDelete('cascade');
            $table->foreignId('producto_id')->constrained();
            $table->foreignId('unidad_medida_id')->constrained('unidad_medidas');
            $table->decimal('cantidad', 10, 2);
            $table->decimal('precio', 10, 2);
            $table->decimal('descuento', 10, 2)->default(0);
            $table->decimal('subtotal', 10, 2);
            $table->decimal('impuesto', 10, 2);
            $table->decimal('total', 10, 2);
            $table->timestamps();
        });

        Schema::create('compras', function (Blueprint $table) {
            $table->id();
            $table->string('numero')->unique();
            $table->foreignId('proveedor_id')->constrained('proveedores');
            $table->foreignId('user_id')->constrained();
            $table->date('fecha');
            $table->string('tipo_comprobante', 20)->comment('01-FACTURA, 02-NOTA_CREDITO, 03-NOTA_DEBITO');
            $table->string('ncf', 19);
            $table->decimal('subtotal', 12, 2);
            $table->decimal('impuesto', 12, 2);
            $table->decimal('descuento', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->enum('estado', ['PENDIENTE', 'COMPLETADA', 'ANULADA'])->default('PENDIENTE');
            $table->text('observacion')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['ncf', 'proveedor_id']);
        });

        Schema::create('compra_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compra_id')->constrained()->onDelete('cascade');
            $table->foreignId('producto_id')->constrained();
            $table->foreignId('unidad_medida_id')->constrained('unidad_medidas');
            $table->decimal('cantidad', 10, 2);
            $table->decimal('precio', 10, 2);
            $table->decimal('descuento', 10, 2)->default(0);
            $table->decimal('subtotal', 10, 2);
            $table->decimal('impuesto', 10, 2);
            $table->decimal('total', 10, 2);
            $table->timestamps();
        });

        Schema::create('secuencias_ncf', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 20)->comment('01-FACTURA, 02-NOTA_CREDITO, 03-NOTA_DEBITO');
            $table->string('prefijo', 3)->comment('B01, B02, etc.');
            $table->integer('secuencia')->default(1);
            $table->integer('secuencia_inicial');
            $table->integer('secuencia_final');
            $table->date('fecha_vencimiento')->nullable();
            $table->boolean('is_activo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tipo', 'prefijo']);
        });
    }

    public function down(): void
    {
        // Eliminamos en orden inverso para respetar las dependencias
        Schema::dropIfExists('secuencias_ncf');
        Schema::dropIfExists('compra_detalles');
        Schema::dropIfExists('compras');
        Schema::dropIfExists('venta_detalles');
        Schema::dropIfExists('ventas');
        Schema::dropIfExists('historial_costos');
        Schema::dropIfExists('movimiento_inventarios');
        Schema::dropIfExists('detalle_unidad_productos');
        Schema::dropIfExists('productos');
        Schema::dropIfExists('direcciones');
        Schema::dropIfExists('clientes');
        Schema::dropIfExists('proveedores');
    }
}; 