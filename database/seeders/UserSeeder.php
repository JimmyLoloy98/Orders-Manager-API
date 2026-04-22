<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Admin user
        User::create([
            'name' => 'Administrador',
            'username' => 'admin',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
        ]);

        // Waiter user
        User::create([
            'name' => 'Mozo Principal',
            'username' => 'mozo',
            'password' => Hash::make('mozo123'),
            'role' => 'mozo',
        ]);
    }
}
