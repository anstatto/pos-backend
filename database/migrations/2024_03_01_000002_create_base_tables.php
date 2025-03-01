<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tablas de configuración
        Schema::create('condicion_pagos', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('dias')->default(0);
            $table->boolean('is_activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('unidad_medidas', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('abreviatura', 10);
            $table->boolean('is_activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('categorias', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('descripcion')->nullable();
            $table->boolean('is_activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Tablas de roles y permisos
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('descripcion')->nullable();
            $table->boolean('is_activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('permisos', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('descripcion')->nullable();
            $table->string('grupo');
            $table->boolean('is_activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Las relaciones con users se crean después de que exista la tabla users
        if (Schema::hasTable('users')) {
            Schema::create('role_permiso', function (Blueprint $table) {
                $table->id();
                $table->foreignId('role_id')->constrained()->onDelete('cascade');
                $table->foreignId('permiso_id')->constrained()->onDelete('cascade');
                $table->timestamps();
    
                $table->unique(['role_id', 'permiso_id']);
            });
    
            Schema::create('role_user', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('role_id')->constrained()->onDelete('cascade');
                $table->timestamps();
    
                $table->unique(['user_id', 'role_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('role_permiso');
        Schema::dropIfExists('permisos');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('categorias');
        Schema::dropIfExists('unidad_medidas');
        Schema::dropIfExists('condicion_pagos');
    }
}; 