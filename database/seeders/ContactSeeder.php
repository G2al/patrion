<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ContactStatus;
use App\Enums\Priority;
use App\Enums\ProspectSource;
use App\Models\Contact;

final class ContactSeeder extends DemoSeeder
{
    public function run(): void
    {
        $contacts = [
            ['first_name' => 'Luigi', 'last_name' => 'Iommelli', 'email' => 'luigi.iommelli@example.test', 'phone' => '+39 333 120 4587', 'status' => ContactStatus::Client, 'priority' => Priority::High, 'profession' => 'Titolare d’impresa', 'managed_assets' => 185000, 'relationship_level' => 'Consolidato', 'preferred_communication' => 'in_person', 'relationship_notes' => 'Preferisce incontrarsi la mattina: è una persona mattiniera.', 'important_information' => 'Pianifica gli incontri con qualche giorno di anticipo.', 'next_follow_up_at' => now()->addDays(7)],
            ['first_name' => 'Giulia', 'last_name' => 'Bianchi', 'email' => 'giulia.bianchi@example.test', 'phone' => '+39 347 845 2019', 'status' => ContactStatus::Client, 'priority' => Priority::Medium, 'profession' => 'Architetta', 'managed_assets' => 92000, 'relationship_level' => 'Buono', 'preferred_communication' => 'phone', 'relationship_notes' => 'Preferisce ricevere un riepilogo via email dopo ogni incontro.', 'next_follow_up_at' => now()->addDays(14)],
            ['first_name' => 'Marco', 'last_name' => 'Rinaldi', 'email' => 'marco.rinaldi@example.test', 'phone' => '+39 328 672 1140', 'status' => ContactStatus::Client, 'priority' => Priority::Medium, 'profession' => 'Commercialista', 'managed_assets' => 240000, 'relationship_level' => 'Consolidato', 'preferred_communication' => 'email', 'relationship_notes' => 'Vuole valutare ogni proposta con il consulente fiscale.', 'next_follow_up_at' => now()->addDays(21)],
            ['first_name' => 'Alessandra', 'last_name' => 'Costantini', 'email' => 'alessandra.costantini@example.test', 'phone' => '+39 349 510 7782', 'status' => ContactStatus::Prospect, 'priority' => Priority::High, 'profession' => 'Responsabile HR', 'potential_value' => 68000, 'source' => ProspectSource::Referral, 'preferred_communication' => 'video_call', 'relationship_notes' => 'Sta valutando una soluzione di previdenza e protezione familiare.', 'next_follow_up_at' => now()->addDays(5)],
            ['first_name' => 'Davide', 'last_name' => 'Moretti', 'email' => 'davide.moretti@example.test', 'phone' => '+39 366 293 6401', 'status' => ContactStatus::Prospect, 'priority' => Priority::Medium, 'profession' => 'Ingegnere', 'potential_value' => 42000, 'source' => ProspectSource::Event, 'preferred_communication' => 'phone', 'relationship_notes' => 'Ha chiesto un confronto sui costi prima di fissare il prossimo incontro.', 'next_follow_up_at' => now()->addDays(10)],
        ];

        foreach ($contacts as $index => $data) {
            Contact::query()->updateOrCreate(['email' => $data['email']], [...$data, 'tax_code' => 'PATR'.str_pad((string) ($index + 1), 10, '0', STR_PAD_LEFT), 'first_contact_date' => now()->subDays(30 + ($index * 12)), 'last_contact_at' => now()->subDays(2 + $index), 'interests' => $data['status'] === ContactStatus::Client ? ['investments', 'protection'] : ['pension', 'savings'], 'personal_goals' => ['retirement', 'savings']]);
        }

        $profiles = [
            'luigi.iommelli@example.test' => ['client_type' => 'private', 'tags' => ['Premium', 'Famiglia'], 'relationship_score' => 5],
            'giulia.bianchi@example.test' => ['client_type' => 'private', 'tags' => ['Famiglia'], 'relationship_score' => 4],
            'marco.rinaldi@example.test' => ['client_type' => 'business', 'tags' => ['Premium'], 'relationship_score' => 4],
            'alessandra.costantini@example.test' => ['client_type' => 'private', 'tags' => ['Famiglia'], 'relationship_score' => 3],
            'davide.moretti@example.test' => ['client_type' => 'private', 'tags' => ['HNWI'], 'relationship_score' => 2],
        ];
        foreach ($profiles as $email => $profile) {
            Contact::query()->where('email', $email)->update([...$profile, 'assigned_user_id' => $this->owner()->id]);
        }
    }
}
