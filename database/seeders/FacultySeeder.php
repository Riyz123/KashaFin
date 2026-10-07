<?php

namespace Database\Seeders;

use App\Models\Faculty;
use Illuminate\Database\Seeder;

class FacultySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (['Ingeniería', 'Ciencias de la Salud', 'Negocios', 'Comunicaciones', 'Derecho', 'Arquitectura'] as $name) {
            Faculty::firstOrCreate(['name' => $name]);
        }
    }
}
