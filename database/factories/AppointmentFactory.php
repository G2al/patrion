<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AppointmentOutcome;
use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Appointment> */
class AppointmentFactory extends Factory
{
    public function definition(): array
    {
        $startsAt = fake()->dateTimeBetween('-30 days', '+30 days');

        return ['title' => fake()->randomElement(['Analisi delle esigenze', 'Revisione annuale', 'Presentazione proposta', 'Aggiornamento pratica']), 'description' => fake()->sentence(), 'contact_id' => Contact::factory(), 'company_id' => null, 'starts_at' => $startsAt, 'ends_at' => (clone $startsAt)->modify('+1 hour'), 'mode' => fake()->randomElement(['in_person', 'phone', 'video_call']), 'status' => AppointmentStatus::Scheduled, 'owner_id' => User::factory()];
    }

    public function scheduled(): static
    {
        return $this->state(fn (): array => ['status' => AppointmentStatus::Scheduled, 'reported_at' => null]);
    }

    public function completed(): static
    {
        return $this->state(fn (): array => ['status' => AppointmentStatus::Completed, 'outcome' => AppointmentOutcome::Positive, 'reported_at' => now()]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (): array => ['status' => AppointmentStatus::Cancelled]);
    }

    public function noShow(): static
    {
        return $this->state(fn (): array => ['status' => AppointmentStatus::NoShow]);
    }

    public function forContact(Contact $contact): static
    {
        return $this->for($contact, 'contact')->state(fn (): array => ['company_id' => null]);
    }
}
