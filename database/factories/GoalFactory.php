<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\GoalStatus;
use App\Models\Goal;
use App\Models\PracticeType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Goal> */
class GoalFactory extends Factory
{
    public function definition(): array
    {
        return ['title' => 'Obiettivo '.fake()->words(2, true), 'description' => fake()->sentence(), 'practice_type_id' => PracticeType::factory(), 'target_quantity' => fake()->numberBetween(3, 15), 'starts_at' => today()->startOfMonth(), 'ends_at' => today()->endOfMonth(), 'status' => GoalStatus::Active, 'owner_id' => User::factory()];
    }

    public function active(): static
    {
        return $this->state(fn (): array => ['status' => GoalStatus::Active]);
    }
}
