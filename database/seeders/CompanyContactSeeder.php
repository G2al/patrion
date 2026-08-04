<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Contact;

final class CompanyContactSeeder extends DemoSeeder
{
    public function run(): void
    {
        $rinaldi = Company::query()->where('vat_number', 'IT09123456781')->firstOrFail();
        $costantini = Company::query()->where('vat_number', 'IT09876543210')->firstOrFail();
        $rinaldi->contacts()->syncWithoutDetaching([Contact::query()->where('email', 'marco.rinaldi@example.test')->value('id') => ['role' => 'Titolare']]);
        $costantini->contacts()->syncWithoutDetaching([Contact::query()->where('email', 'alessandra.costantini@example.test')->value('id') => ['role' => 'Responsabile HR']]);
    }
}
