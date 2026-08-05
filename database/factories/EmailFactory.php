<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

final class EmailFactory extends Factory
{
    public function definition(): array
    {
        $body = fake()->paragraph();

        return ['user_id' => User::factory(), 'contact_id' => Contact::factory(), 'sender_name' => fake()->name(), 'sender_email' => fake()->safeEmail(), 'recipient_email' => fake()->safeEmail(), 'subject' => fake()->sentence(4), 'body' => $body, 'preview' => $body, 'direction' => 'incoming', 'is_read' => false, 'is_important' => false, 'received_at' => now()];
    }
}
