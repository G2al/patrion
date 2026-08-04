<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Practice;
use Illuminate\Database\Eloquent\Model;

class NoteSeeder extends DemoSeeder
{
    public function run(): void
    {
        $owner = $this->owner();
        $contacts = Contact::query()->where('tax_code', 'like', 'DMOC%')->orderBy('id')->get();
        $companies = Company::query()->where('name', 'like', 'Azienda Demo %')->orderBy('id')->get();
        $practices = Practice::query()->where('internal_number', 'like', 'DEMO-PR-%')->orderBy('id')->get();
        $appointments = Appointment::query()->where('title', 'like', 'DEMO Appuntamento %')->orderBy('id')->get();

        foreach (range(1, 40) as $index) {
            $subject = match (true) {
                $index <= 20 => $contacts[($index - 1) % $contacts->count()],
                $index <= 28 => $companies[($index - 1) % $companies->count()],
                $index <= 35 => $practices[($index - 1) % $practices->count()],
                default => $appointments[($index - 1) % $appointments->count()],
            };
            $this->createNote($subject, $index, $owner->id);
        }
    }

    private function createNote(Model $subject, int $index, int $authorId): void
    {
        $subject->notes()->firstOrCreate(['title' => sprintf('Nota demo %02d', $index)], [
            'content' => 'Informazione dimostrativa sulla relazione, priva di dati personali reali.',
            'is_important' => $index % 7 === 0,
            'author_id' => $authorId,
        ]);
    }
}
