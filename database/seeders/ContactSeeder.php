<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Contact;

class ContactSeeder extends DemoSeeder
{
    public function run(): void
    {
        foreach (range(1, 45) as $index) {
            $taxCode = sprintf('DMOC%012d', $index);

            if (Contact::query()->where('tax_code', $taxCode)->exists()) {
                continue;
            }

            $factory = $index <= 25 ? Contact::factory()->client() : Contact::factory()->prospect();
            $factory = match (true) {
                $index % 9 === 0 => $factory->highPriority()->withExpiredFollowUp(),
                $index % 5 === 0 => $factory->withExpiredFollowUp(),
                default => $factory->withUpcomingFollowUp(),
            };

            $factory->create([
                'tax_code' => $taxCode,
                'last_contact_at' => $index % 7 === 0 ? null : now()->subDays(($index % 20) + 1),
            ]);
        }
    }
}
