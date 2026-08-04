<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Company> */
class CompanyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company(), 'vat_number' => fake()->unique()->numerify('###########'),
            'tax_code' => fake()->unique()->numerify('###########'), 'rea_number' => 'MI-'.fake()->unique()->numerify('######'),
            'pec' => fake()->unique()->companyEmail(), 'sdi_code' => strtoupper(fake()->bothify('??#####')),
            'address' => fake()->address(), 'phone' => fake()->phoneNumber(), 'email' => fake()->companyEmail(), 'website' => fake()->url(),
            'industry' => fake()->randomElement(['Tecnologia', 'Manifattura', 'Commercio', 'Servizi', 'Edilizia']),
            'ateco_code' => fake()->numerify('##.##.##'), 'revenue' => fake()->randomFloat(2, 300000, 12000000),
            'employees_count' => fake()->numberBetween(3, 150), 'shareholders_count' => fake()->numberBetween(1, 8),
            'liquidity' => fake()->randomFloat(2, 50000, 1500000), 'investments' => fake()->randomFloat(2, 0, 1000000),
            'opportunities' => fake()->randomElements(['investments', 'liquidity', 'financing', 'welfare', 'pension', 'protection'], fake()->numberBetween(1, 3)),
        ];
    }
}
