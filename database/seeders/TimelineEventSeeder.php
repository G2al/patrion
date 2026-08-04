<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ActivityStatus;
use App\Enums\PracticeStatus;
use App\Models\Activity;
use App\Models\Appointment;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Document;
use App\Models\Note;
use App\Models\Practice;
use App\Services\TimelineRecorder;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

class TimelineEventSeeder extends DemoSeeder
{
    public function run(): void
    {
        $owner = $this->owner();
        $contacts = Contact::query()->where('tax_code', 'like', 'DMOC%')->orderBy('id')->get();
        $companies = Company::query()->where('name', 'like', 'Azienda Demo %')->orderBy('id')->get();

        foreach (range(1, 30) as $index) {
            $subject = $index % 4 === 0 ? $companies[($index - 1) % $companies->count()] : $contacts[($index - 1) % $contacts->count()];
            $subject->timelineEvents()->firstOrCreate(['title' => sprintf('Aggiornamento demo %02d', $index)], [
                'event_type' => $index % 3 === 0 ? 'follow_up_scheduled' : 'relationship_updated',
                'description' => 'Evento dimostrativo coerente con la gestione della relazione.',
                'metadata' => ['demo' => true, 'sequence' => $index],
                'occurred_at' => now()->subDays($index)->setTime(9 + ($index % 8), 0),
                'author_id' => $owner->id,
            ]);
        }

        foreach ($contacts as $contact) {
            $this->record($contact, 'contact_created', 'Contatto creato', $contact->first_contact_date ?? $contact->created_at, $owner->id);
        }

        foreach ($companies as $company) {
            $this->record($company, 'company_created', 'Azienda creata', now()->subYear()->addMinutes($company->id), $owner->id);
        }

        foreach (Appointment::query()->where('title', 'like', 'DEMO Appuntamento %')->with(['contact', 'company'])->get() as $appointment) {
            $subject = app(TimelineRecorder::class)->resolveSubject($appointment);
            if ($subject) {
                $this->record($subject, 'appointment_created', "Appuntamento creato: {$appointment->title}", $appointment->starts_at->copy()->subDays(2), $owner->id);
                if ($appointment->reported_at) {
                    $this->record($subject, 'appointment_reported', "Appuntamento consuntivato: {$appointment->title}", $appointment->reported_at, $owner->id);
                }
            }
        }

        foreach (Practice::query()->where('internal_number', 'like', 'DEMO-PR-%')->with(['contact', 'company'])->get() as $practice) {
            $subject = app(TimelineRecorder::class)->resolveSubject($practice);
            if ($subject) {
                $this->record($subject, 'practice_opened', "Pratica aperta: {$practice->internal_number}", $practice->opened_at, $owner->id);
                if ($practice->status === PracticeStatus::Completed && $practice->completed_at) {
                    $this->record($subject, 'practice_completed', "Pratica completata: {$practice->internal_number}", $practice->completed_at, $owner->id);
                }
            }
        }

        foreach (Activity::query()->where('title', 'like', 'DEMO Attività %')->where('status', ActivityStatus::Completed)->with(['contact', 'company', 'practice', 'appointment'])->get() as $activity) {
            $subject = app(TimelineRecorder::class)->resolveSubject($activity);
            if ($subject && $activity->completed_at) {
                $this->record($subject, 'activity_completed', "Attività completata: {$activity->title}", $activity->completed_at, $owner->id);
            }
        }

        foreach (Document::query()->where('file_path', 'like', 'demo-documents/%')->with(['contact', 'company', 'practice'])->get() as $document) {
            $subject = app(TimelineRecorder::class)->resolveSubject($document);
            if ($subject) {
                $this->record($subject, 'document_uploaded', "Documento caricato: {$document->name}", $document->document_date ?? $document->created_at, $owner->id);
            }
        }

        foreach (Note::query()->where('title', 'like', 'Nota demo %')->with('noteable')->get() as $note) {
            $subject = $note->noteable instanceof Model ? app(TimelineRecorder::class)->resolveSubject($note->noteable) : null;
            if ($subject) {
                $this->record($subject, 'note_added', "Nota inserita: {$note->title}", $note->created_at, $owner->id);
            }
        }
    }

    private function record(Contact|Company $subject, string $type, string $title, CarbonInterface $occurredAt, int $authorId): void
    {
        $subject->timelineEvents()->firstOrCreate(['event_type' => $type, 'title' => $title], [
            'description' => 'Evento demo generato da dati operativi coerenti.',
            'metadata' => ['demo' => true],
            'occurred_at' => $occurredAt,
            'author_id' => $authorId,
        ]);
    }
}
