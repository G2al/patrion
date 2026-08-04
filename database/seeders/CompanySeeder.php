<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Company;

class CompanySeeder extends DemoSeeder
{
    public function run(): void
    {
        foreach (range(1, 12) as $index) {
            $vatNumber = sprintf('91%09d', $index);

            if (! Company::query()->where('vat_number', $vatNumber)->exists()) {
                Company::factory()->create(['name' => "Azienda Demo {$index} S.r.l.", 'vat_number' => $vatNumber, 'tax_code' => sprintf('92%09d', $index)]);
            }
        }
    }
}
