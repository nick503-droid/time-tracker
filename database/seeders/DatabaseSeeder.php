<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Tu usuario Administrador
        User::create([
            'name' => 'Walter Admin',
            'email' => 'admin@test.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'must_change_password' => false, // Para que no te pida cambiar clave a cada rato
        ]);

        // 2. Empleado para hacer pruebas (Ya con horario)
        User::create([
            'name' => 'Empleado de Prueba',
            'email' => 'empleado@test.com',
            'password' => Hash::make('password123'),
            'role' => 'employee',
            'must_change_password' => false,
            'scheduled_in' => '08:00:00',
            'scheduled_out' => '17:00:00',
            'break_duration_minutes' => 15,
            'lunch_duration_minutes' => 60,
            'max_breaks_per_day' => 2,
        ]);
    }
}
