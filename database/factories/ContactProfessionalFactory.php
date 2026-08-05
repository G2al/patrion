<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Contact;
use Illuminate\Database\Eloquent\Factories\Factory;

final class ContactProfessionalFactory extends Factory
{
    public function definition(): array
    {
        return ['contact_id' => Contact::factory(), 'name' => fake()->name(), 'role' => fake()->jobTitle(), 'company_name' => fake()->company(), 'email' => fake()->safeEmail(), 'phone' => fake()->phoneNumber(), 'notes' => fake()->optional()->sentence()];
    }
}
