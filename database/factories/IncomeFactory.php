<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Income>
 */
class IncomeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'amount' => fake()->randomFloat(2, 50, 300),
            'date' => fake()->dateTimeBetween('-28 days', 'now'),
            'description' => fake()->randomElement(['Trabajo freelance', 'Venta de apuntes', 'Propina', 'Venta ocasional']),
            'type' => 'variable',
            'frequency' => null,
            'next_occurrence_date' => null,
        ];
    }
}
