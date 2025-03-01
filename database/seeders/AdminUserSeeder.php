<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run()
    {
        // Crear usuario administrador
        $admin = User::create([
            'name' => 'Administrador',
            'email' => 'admin@sistema.com',
            'password' => Hash::make('admin123'),
            'is_activo' => true
        ]);

        // Asignar rol de administrador
        $adminRole = Role::where('name', 'Administrador')->first();
        $admin->roles()->attach($adminRole);
    }
} 