<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Contact;
use App\Models\ContactGoal;

final class ContactGoalSeeder extends DemoSeeder
{
    public function run(): void
    {
        $contacts = Contact::query()->get()->keyBy('email');
        foreach ([['contact' => 'luigi.iommelli@example.test', 'title' => 'Costituire una riserva di liquidità', 'description' => 'Mantenere una riserva per esigenze familiari e aziendali.', 'status' => 'in_progress', 'due_date' => now()->addMonths(8)->toDateString(), 'progress_percentage' => 45], ['contact' => 'giulia.bianchi@example.test', 'title' => 'Proteggere il reddito familiare', 'description' => 'Valutare la copertura più adatta al nucleo familiare.', 'status' => 'planned', 'due_date' => now()->addMonths(5)->toDateString(), 'progress_percentage' => 10], ['contact' => 'alessandra.costantini@example.test', 'title' => 'Pianificare la previdenza', 'description' => 'Raccogliere informazioni e definire un piano previdenziale.', 'status' => 'planned', 'due_date' => now()->addMonths(4)->toDateString(), 'progress_percentage' => 20]] as $data) {
            $contact = $contacts[$data['contact']];
            unset($data['contact']);
            ContactGoal::query()->updateOrCreate(['contact_id' => $contact->id, 'title' => $data['title']], $data);
        }
    }
}
