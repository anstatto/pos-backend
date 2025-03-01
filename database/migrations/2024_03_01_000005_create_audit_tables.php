<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tablas de auditoría
        Schema::dropIfExists('auditorias');
        Schema::create('auditorias', function (Blueprint $table) {
            $table->id();
            $table->string('auditable_type');
            $table->unsignedBigInteger('auditable_id');
            $table->string('evento');
            $table->json('valores_antiguos')->nullable();
            $table->json('valores_nuevos')->nullable();
            $table->foreignId('user_id')->nullable()->constrained();
            $table->string('url')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
        });

        // Tablas de caja y pagos
        Schema::dropIfExists('cajas');
        Schema::create('cajas', function (Blueprint $table) {
            $table->id();
            $table->string('numero', 20)->unique();
            $table->foreignId('user_id')->constrained();
            $table->decimal('monto_inicial', 12, 2);
            $table->decimal('monto_final', 12, 2)->nullable();
            $table->decimal('diferencia', 12, 2)->nullable();
            $table->enum('estado', ['ABIERTA', 'CERRADA'])->default('ABIERTA');
            $table->timestamp('fecha_apertura')->useCurrent();
            $table->timestamp('fecha_cierre')->nullable();
            $table->text('nota')->nullable();
            $table->boolean('is_activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::dropIfExists('pagos');
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->string('numero', 20)->unique();
            $table->string('pagable_type');
            $table->unsignedBigInteger('pagable_id');
            $table->decimal('monto', 12, 2);
            $table->enum('tipo_pago', ['EFECTIVO', 'TARJETA', 'TRANSFERENCIA', 'CHEQUE']);
            $table->string('referencia', 50)->nullable();
            $table->foreignId('user_id')->constrained();
            $table->text('nota')->nullable();
            $table->boolean('is_activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::dropIfExists('cuentas_por_cobrar');
        Schema::create('cuentas_por_cobrar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')->constrained();
            $table->foreignId('cliente_id')->constrained();
            $table->decimal('monto_original', 12, 2);
            $table->decimal('monto_pendiente', 12, 2);
            $table->date('fecha_vencimiento');
            $table->enum('estado', ['PENDIENTE', 'PAGADA', 'VENCIDA'])->default('PENDIENTE');
            $table->boolean('is_activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::dropIfExists('cuentas_por_pagar');
        Schema::create('cuentas_por_pagar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compra_id')->constrained();
            $table->foreignId('proveedor_id')->constrained('proveedores');
            $table->decimal('monto_original', 12, 2);
            $table->decimal('monto_pendiente', 12, 2);
            $table->date('fecha_vencimiento');
            $table->enum('estado', ['PENDIENTE', 'PAGADA', 'VENCIDA'])->default('PENDIENTE');
            $table->boolean('is_activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Crear índices después de que todas las tablas existan
        Schema::table('auditorias', function (Blueprint $table) {
            $table->index(['auditable_type', 'auditable_id']);
        });

        Schema::table('cajas', function (Blueprint $table) {
            $table->index(['user_id', 'estado', 'fecha_apertura']);
        });

        Schema::table('pagos', function (Blueprint $table) {
            $table->index(['pagable_type', 'pagable_id']);
        });

        Schema::table('cuentas_por_cobrar', function (Blueprint $table) {
            $table->index(['cliente_id', 'estado', 'fecha_vencimiento']);
        });

        Schema::table('cuentas_por_pagar', function (Blueprint $table) {
            $table->index(['proveedor_id', 'estado', 'fecha_vencimiento']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cuentas_por_pagar');
        Schema::dropIfExists('cuentas_por_cobrar');
        Schema::dropIfExists('pagos');
        Schema::dropIfExists('cajas');
        Schema::dropIfExists('auditorias');
    }
}; 