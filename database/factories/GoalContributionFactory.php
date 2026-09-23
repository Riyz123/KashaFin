<?php

namespace Database\Factories;

use App\Models\SavingsGoal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\GoalContribution>
 */
class GoalContributionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'savings_goal_id' => SavingsGoal::factory(),
            'user_id' => User::factory(),
            'amount' => fake()->randomFloat(2, 50, 300),
            'date' => fake()->dateTimeBetween('-30 days', 'now'),
            'note' => null,
        ];
    }
}
