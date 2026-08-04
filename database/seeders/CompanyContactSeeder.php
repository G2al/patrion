<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Contact;

class CompanyContactSeeder extends DemoSeeder
{
    public function run(): void
    {
        $companies = Company::query()->where('name', 'like', 'Azienda Demo %')->orderBy('id')->get();
        $contacts = Contact::query()->where('tax_code', 'like', 'DMOC%')->orderBy('id')->get();
        $roles = ['administrator', 'shareholder', 'cfo', 'accountant', 'manager', 'contact_person'];

        foreach (range(0, 23) as $index) {
            $company = $companies[$index % $companies->count()];
            $contact = $contacts[$index % $contacts->count()];
            $company->contacts()->syncWithoutDetaching([$contact->id => ['role' => $roles[$index % count($roles)]]]);
        }
    }
}
