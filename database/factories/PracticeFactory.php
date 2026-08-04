<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PracticeStatus;
use App\Enums\Priority;
use App\Models\Contact;
use App\Models\Practice;
use App\Models\PracticeType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Practice> */
class PracticeFactory extends Factory
{
    public function definition(): array
    {
        return ['internal_number' => 'DEMO-'.fake()->unique()->numerify('######'), 'title' => fake()->sentence(4), 'description' => fake()->sentence(), 'practice_type_id' => PracticeType::factory(), 'contact_id' => Contact::factory()->client(), 'company_id' => null, 'status' => PracticeStatus::Draft, 'priority' => fake()->randomElement(Priority::cases()), 'opened_at' => fake()->dateTimeBetween('-6 months'), 'expected_at' => fake()->dateTimeBetween('now', '+3 months'), 'owner_id' => User::factory()];
    }

    public function draft(): static
    {
        return $this->state(fn (): array => ['status' => PracticeStatus::Draft, 'completed_at' => null]);
    }

    public function inProgress(): static
    {
        return $this->state(fn (): array => ['status' => PracticeStatus::InProgress, 'completed_at' => null]);
    }

    public function waiting(): static
    {
        return $this->state(fn (): array => ['status' => PracticeStatus::Waiting, 'completed_at' => null]);
    }

    public function completed(): static
    {
        return $this->state(fn (): array => ['status' => PracticeStatus::Completed, 'completed_at' => fake()->dateTimeBetween('-30 days')]);
    }

    public function unsuccessful(): static
    {
        return $this->state(fn (): array => ['status' => PracticeStatus::Unsuccessful, 'completed_at' => null]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (): array => ['status' => PracticeStatus::Cancelled, 'completed_at' => null]);
    }
}
