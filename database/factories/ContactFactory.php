<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ContactStatus;
use App\Enums\Priority;
use App\Enums\ProspectSource;
use App\Models\Contact;
use App\Models\Note;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\Sequence;

/** @extends Factory<Contact> */
class ContactFactory extends Factory
{
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(), 'last_name' => fake()->lastName(),
            'birth_date' => fake()->dateTimeBetween('-75 years', '-25 years'), 'birth_place' => fake()->city(),
            'tax_code' => strtoupper(fake()->unique()->bothify('??????##?##?###?')), 'profession' => fake()->jobTitle(),
            'residence' => fake()->address(), 'email' => fake()->unique()->safeEmail(), 'phone' => fake()->phoneNumber(), 'whatsapp' => fake()->phoneNumber(),
            'status' => ContactStatus::Prospect, 'first_contact_date' => fake()->dateTimeBetween('-2 years'),
            'source' => fake()->randomElement(ProspectSource::cases()), 'priority' => fake()->randomElement(Priority::cases()),
            'potential_value' => fake()->randomFloat(2, 10000, 500000), 'relationship_level' => fake()->randomElement(['Iniziale', 'Buono', 'Consolidato']),
            'last_contact_at' => fake()->optional()->dateTimeBetween('-3 months'), 'next_follow_up_at' => fake()->optional()->dateTimeBetween('-10 days', '+30 days'),
            'interests' => fake()->randomElements(['investments', 'pension', 'protection', 'company', 'other'], fake()->numberBetween(1, 3)),
            'personal_goals' => fake()->randomElements(['retirement', 'savings', 'protection', 'children', 'home', 'income', 'succession', 'company'], fake()->numberBetween(1, 3)),
            'preferred_communication' => fake()->randomElement(['phone', 'email', 'whatsapp', 'in_person']),
            'hobbies' => fake()->randomElements(['Viaggi', 'Tennis', 'Lettura', 'Formula 1', 'Cucina'], fake()->numberBetween(1, 2)),
            'family_information' => fake()->optional()->sentence(), 'important_information' => fake()->optional()->sentence(),
        ];
    }

    public function prospect(): static
    {
        return $this->state(fn (): array => ['status' => ContactStatus::Prospect]);
    }

    public function client(): static
    {
        return $this->state(fn (): array => ['status' => ContactStatus::Client]);
    }

    public function highPriority(): static
    {
        return $this->state(fn (): array => ['priority' => Priority::High]);
    }

    public function withUpcomingFollowUp(): static
    {
        return $this->state(fn (): array => ['next_follow_up_at' => now()->addDays(fake()->numberBetween(1, 14))]);
    }

    public function withExpiredFollowUp(): static
    {
        return $this->state(fn (): array => ['next_follow_up_at' => now()->subDays(fake()->numberBetween(1, 14))]);
    }

    public function demoSequence(): static
    {
        return $this->state(new Sequence(
            ['priority' => Priority::High, 'status' => ContactStatus::Prospect],
            ['priority' => Priority::Medium, 'status' => ContactStatus::Client],
            ['priority' => Priority::Low, 'status' => ContactStatus::Client],
        ));
    }

    public function withNotes(int $count = 1): static
    {
        return $this->has(Note::factory()->count($count), 'notes');
    }
}
