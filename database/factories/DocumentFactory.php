<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DocumentStatus;
use App\Models\Contact;
use App\Models\Document;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Storage;

/** @extends Factory<Document> */
class DocumentFactory extends Factory
{
    public function definition(): array
    {
        $name = 'documento-demo-'.fake()->unique()->numerify('####').'.txt';

        return ['name' => $name, 'category' => fake()->randomElement(['Documento di identità', 'Contratto', 'Questionario', 'Estratto conto', 'Bilancio', 'Visura']), 'disk' => 'local', 'file_path' => "demo-documents/{$name}", 'description' => 'Documento dimostrativo privo di dati reali.', 'contact_id' => Contact::factory(), 'company_id' => null, 'document_date' => fake()->dateTimeBetween('-1 year'), 'expires_at' => fake()->optional()->dateTimeBetween('-30 days', '+6 months'), 'status' => DocumentStatus::Valid, 'uploaded_by_id' => User::factory()];
    }

    public function expired(): static
    {
        return $this->state(fn (): array => ['expires_at' => now()->subDays(fake()->numberBetween(1, 60)), 'status' => DocumentStatus::Expired]);
    }

    public function expiringSoon(): static
    {
        return $this->state(fn (): array => ['expires_at' => now()->addDays(fake()->numberBetween(1, 30)), 'status' => DocumentStatus::Valid]);
    }

    public function withPhysicalFile(): static
    {
        return $this->afterCreating(function (Document $document): void {
            Storage::disk($document->disk)->put($document->file_path, 'Documento demo innocuo generato dalla factory.');
        });
    }
}
