<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\SavingsGoal>
 */
class SavingsGoalFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->randomElement(['Laptop nueva', 'Viaje de graduación', 'Fondo de emergencia']),
            'target_amount' => fake()->randomFloat(2, 500, 3000),
            'target_date' => fake()->dateTimeBetween('+2 months', '+8 months'),
            'current_amount' => 0,
            'status' => 'active',
        ];
    }
}
