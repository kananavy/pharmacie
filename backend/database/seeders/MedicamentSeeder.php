<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Medicament;

class MedicamentSeeder extends Seeder
{
    public function run(): void
    {
        Medicament::create([
            'nom' => 'Paracétamol 500mg',
            'code' => 'MED001',
            'categorie' => 'Analgésique',
            'prix' => 2.50,
            'stock' => 100,
            'date_expiration' => '2027-12-31',
            'ordonnance_requise' => false,
        ]);

        Medicament::create([
            'nom' => 'Amoxicilline 1g',
            'code' => 'MED002',
            'categorie' => 'Antibiotique',
            'prix' => 12.00,
            'stock' => 50,
            'date_expiration' => '2026-06-30',
            'ordonnance_requise' => true,
        ]);

        Medicament::create([
            'nom' => 'Doliprane 1000mg',
            'code' => 'MED003',
            'categorie' => 'Analgésique',
            'prix' => 3.20,
            'stock' => 5, // Low stock for alert demo
            'date_expiration' => '2028-01-01',
            'ordonnance_requise' => false,
        ]);

        Medicament::create([
            'nom' => 'Sirop Toux',
            'code' => 'MED004',
            'categorie' => 'Sirop',
            'prix' => 5.50,
            'stock' => 20,
            'date_expiration' => '2026-02-15', // Near expiration
            'ordonnance_requise' => false,
        ]);
    }
}
