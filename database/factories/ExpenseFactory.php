<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Expense>
 */
class ExpenseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'category_id' => Category::factory(),
            'amount' => fake()->randomFloat(2, 5, 150),
            'date' => fake()->dateTimeBetween('-60 days', 'now'),
            'description' => fake()->randomElement([
                'Almuerzo', 'Pasaje', 'Fotocopias', 'Internet', 'Café', 'Cine', 'Snacks', 'Uber',
            ]),
        ];
    }
}
