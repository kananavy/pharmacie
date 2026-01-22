<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if admin exists
        if (!User::where('email', 'admin@pharmacie.mg')->exists()) {
            User::create([
                'name' => 'Super Administrateur',
                'email' => 'admin@pharmacie.mg',
                'password' => Hash::make('admin123'), // Default password, change in production
                'role' => 'admin',
            ]);
        }
    }
}
