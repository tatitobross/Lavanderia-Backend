<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = Role::pluck('id', 'role');
        foreach (
            [
                ['username' => 'admin', 'name' => 'Administrador Lavandería', 'email' => 'admin@lavanderia.test', 'role' => 'Administrador'],
                ['username' => 'operador', 'name' => 'Operador de lavandería', 'email' => 'operador@lavanderia.test', 'role' => 'Operador'],
            ] as $data
        ) {
            User::updateOrCreate(['username' => $data['username']], [
                'password' => Hash::make('Hola123'),
                'name' => $data['name'],
                'gender' => 'No especificado',
                'birth' => '2000-01-01',
                'email' => $data['email'],
                'role_id' => $roles[$data['role']],
                'status' => 'active',
            ]);
        }
    }
}
