<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Contact;
use App\Models\ContactProfessional;

final class ContactProfessionalSeeder extends DemoSeeder
{
    public function run(): void
    {
        $contacts = Contact::query()->get()->keyBy('email');
        foreach ([['contact' => 'luigi.iommelli@example.test', 'name' => 'Andrea Ferri', 'role' => 'Commercialista', 'company_name' => 'Studio Ferri & Associati', 'email' => 'andrea.ferri@example.test', 'phone' => '+39 02 5550 1200'], ['contact' => 'marco.rinaldi@example.test', 'name' => 'Elena Conti', 'role' => 'Consulente del lavoro', 'company_name' => 'Conti Consulting', 'email' => 'elena.conti@example.test', 'phone' => '+39 02 5550 1300'], ['contact' => 'giulia.bianchi@example.test', 'name' => 'Paolo Neri', 'role' => 'Avvocato', 'company_name' => 'Studio Neri', 'email' => 'paolo.neri@example.test', 'phone' => '+39 02 5550 1400']] as $data) {
            $contact = $contacts[$data['contact']];
            unset($data['contact']);
            ContactProfessional::query()->updateOrCreate(['contact_id' => $contact->id, 'email' => $data['email']], $data);
        }
    }
}
