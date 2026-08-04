<?php

declare(strict_types=1);

namespace App\Observers;

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
use Illuminate\Database\Eloquent\Model;

class DomainTimelineObserver
{
    public function __construct(private readonly TimelineRecorder $timeline) {}

    public function created(Model $model): void
    {
        if ($model instanceof Contact) {
            $this->timeline->record($model, 'contact_created', 'Contatto creato');
        } elseif ($model instanceof Company) {
            $this->timeline->record($model, 'company_created', 'Azienda creata');
        } elseif ($model instanceof Appointment) {
            $this->recordFor($model, 'appointment_created', 'Appuntamento creato');
        } elseif ($model instanceof Practice) {
            $this->recordFor($model, 'practice_opened', 'Pratica aperta');
        } elseif ($model instanceof Document) {
            $this->recordFor($model, 'document_uploaded', 'Documento caricato');
        } elseif ($model instanceof Note) {
            $this->recordFor($model->noteable, 'note_added', 'Nota inserita');
        }
    }

    public function updated(Model $model): void
    {
        if ($model instanceof Practice && $model->wasChanged('status')) {
            $this->recordFor($model, 'practice_status_changed', 'Stato pratica modificato', [
                'status' => $model->status->value,
            ]);

            if ($model->status === PracticeStatus::Completed) {
                $this->recordFor($model, 'practice_completed', 'Pratica completata');
            }
        }

        if ($model instanceof Activity && $model->wasChanged('status') && $model->status === ActivityStatus::Completed) {
            $this->recordFor($model, 'activity_completed', 'Attività completata');
        }
    }

    /** @param array<string, mixed>|null $metadata */
    private function recordFor(Model $model, string $type, string $title, ?array $metadata = null): void
    {
        $subject = $this->timeline->resolveSubject($model);

        if ($subject) {
            $this->timeline->record($subject, $type, $title, metadata: $metadata);
        }
    }
}
