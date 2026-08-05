<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Contact;
use Illuminate\Database\Eloquent\Factories\Factory;

final class ContactGoalFactory extends Factory
{
    public function definition(): array
    {
        return ['contact_id' => Contact::factory(), 'title' => fake()->sentence(3), 'description' => fake()->optional()->sentence(), 'status' => 'planned', 'due_date' => fake()->optional()->dateTimeBetween('now', '+1 year'), 'progress_percentage' => fake()->numberBetween(0, 100)];
    }
}
