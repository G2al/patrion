<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PracticeType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<PracticeType> */
class PracticeTypeFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return ['name' => ucfirst($name), 'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(100, 999), 'is_active' => true, 'sort_order' => fake()->numberBetween(1, 100)];
    }
}
