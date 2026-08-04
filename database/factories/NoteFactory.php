<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Contact;
use App\Models\Note;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Note> */
class NoteFactory extends Factory
{
    public function definition(): array
    {
        return ['noteable_type' => 'contact', 'noteable_id' => Contact::factory(), 'title' => fake()->optional()->sentence(3), 'content' => fake()->paragraph(), 'is_important' => fake()->boolean(20), 'author_id' => User::factory()];
    }

    public function important(): static
    {
        return $this->state(fn (): array => ['is_important' => true]);
    }
}
