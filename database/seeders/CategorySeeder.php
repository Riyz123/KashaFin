<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            ['name' => 'Transporte', 'icon' => 'expense'],
            ['name' => 'Alimentación', 'icon' => 'expense'],
            ['name' => 'Materiales de estudio', 'icon' => 'document'],
            ['name' => 'Entretenimiento', 'icon' => 'flag'],
            ['name' => 'Otros', 'icon' => 'wallet'],
        ];

        foreach ($defaults as $category) {
            Category::firstOrCreate(
                ['user_id' => null, 'name' => $category['name']],
                ['icon' => $category['icon'], 'is_default' => true]
            );
        }
    }
}
