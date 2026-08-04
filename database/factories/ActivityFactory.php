<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ActivityStatus;
use App\Enums\ActivityType;
use App\Enums\Priority;
use App\Models\Activity;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Activity> */
class ActivityFactory extends Factory
{
    public function definition(): array
    {
        return ['title' => fake()->randomElement(['Richiamare il cliente', 'Inviare documentazione', 'Verificare la pratica', 'Programmare follow-up']), 'description' => fake()->sentence(), 'type' => fake()->randomElement(ActivityType::cases()), 'contact_id' => Contact::factory(), 'company_id' => null, 'scheduled_at' => fake()->dateTimeBetween('-10 days', '+20 days'), 'due_at' => fake()->dateTimeBetween('-5 days', '+20 days'), 'priority' => fake()->randomElement(Priority::cases()), 'status' => ActivityStatus::Pending, 'owner_id' => User::factory()];
    }

    public function pending(): static
    {
        return $this->state(fn (): array => ['status' => ActivityStatus::Pending, 'completed_at' => null]);
    }

    public function inProgress(): static
    {
        return $this->state(fn (): array => ['status' => ActivityStatus::InProgress, 'completed_at' => null]);
    }

    public function completed(): static
    {
        return $this->state(fn (): array => ['status' => ActivityStatus::Completed, 'completed_at' => now()]);
    }

    public function postponed(): static
    {
        return $this->state(fn (): array => ['status' => ActivityStatus::Postponed, 'completed_at' => null]);
    }

    public function overdue(): static
    {
        return $this->pending()->state(fn (): array => ['due_at' => now()->subDays(fake()->numberBetween(1, 10))]);
    }

    public function followUp(): static
    {
        return $this->state(fn (): array => ['type' => ActivityType::FollowUp]);
    }
}
