<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Company;

final class CompanySeeder extends DemoSeeder
{
    public function run(): void
    {
        foreach ([
            ['name' => 'Rinaldi Consulting S.r.l.', 'vat_number' => 'IT09123456781', 'industry' => 'Servizi professionali', 'email' => 'amministrazione@rinaldiconsulting.example.test', 'phone' => '+39 02 5550 1840', 'employees_count' => 12, 'revenue' => 1850000],
            ['name' => 'Costantini Design S.r.l.s.', 'vat_number' => 'IT09876543210', 'industry' => 'Design e architettura', 'email' => 'info@costantinidesign.example.test', 'phone' => '+39 02 5550 2761', 'employees_count' => 7, 'revenue' => 760000],
        ] as $data) {
            Company::query()->updateOrCreate(['vat_number' => $data['vat_number']], $data);
        }
    }
}
