<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Contact;

final class TimelineEventSeeder extends DemoSeeder
{
    public function run(): void
    {
        $owner = $this->owner();
        $contacts = Contact::query()->get()->keyBy('email');
        foreach ([
            ['email' => 'luigi.iommelli@example.test', 'type' => 'contact_created', 'title' => 'Primo contatto registrato', 'description' => 'Avviata la relazione con il cliente.'],
            ['email' => 'alessandra.costantini@example.test', 'type' => 'follow_up_scheduled', 'title' => 'Follow-up programmato', 'description' => 'Previsto un ricontatto per completare la raccolta documenti.'],
            ['email' => 'davide.moretti@example.test', 'type' => 'appointment_reported', 'title' => 'Colloquio con esito negativo', 'description' => 'Il prospect ha richiesto un confronto economico prima di procedere.'],
        ] as $data) {
            $contacts[$data['email']]->timelineEvents()->updateOrCreate(['event_type' => $data['type'], 'title' => $data['title']], ['description' => $data['description'], 'metadata' => ['seed' => 'coherent'], 'occurred_at' => now()->subDays(3), 'author_id' => $owner->id]);
        }
    }
}
