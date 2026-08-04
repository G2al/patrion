<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Contact;
use App\Models\TimelineEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TimelineEvent> */
class TimelineEventFactory extends Factory
{
    public function definition(): array
    {
        return ['subject_type' => 'contact', 'subject_id' => Contact::factory(), 'event_type' => fake()->randomElement(['contact_called', 'follow_up_scheduled', 'meeting_note']), 'title' => fake()->randomElement(['Contatto telefonico', 'Follow-up programmato', 'Aggiornamento relazione']), 'description' => fake()->sentence(), 'metadata' => ['demo' => true], 'occurred_at' => fake()->dateTimeBetween('-3 months'), 'author_id' => User::factory()];
    }
}
