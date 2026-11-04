<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Akun Admin (Bisa Login ke Web Filament)
        User::create([
            'name' => 'Super Admin',
            'email' => 'admin@nexastock.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);

        // Akun Staf Gudang (Untuk Testing API Mobile Ionic)
        User::create([
            'name' => 'Staf Lapangan',
            'email' => 'staf@nexastock.com',
            'password' => Hash::make('password123'),
            'role' => 'gudang',
        ]);
    }
}
