<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Admin Pharmacie',
            'email' => 'admin@pharmacie.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        User::create([
            'name' => 'Caissier 1',
            'email' => 'caissier@pharmacie.com',
            'password' => Hash::make('password'),
            'role' => 'caissier',
        ]);
    }
}
