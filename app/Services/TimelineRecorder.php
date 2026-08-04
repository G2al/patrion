<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Company;
use App\Models\Contact;
use App\Models\TimelineEvent;
use Illuminate\Database\Eloquent\Model;

class TimelineRecorder
{
    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function record(
        Contact|Company $subject,
        string $eventType,
        string $title,
        ?string $description = null,
        ?array $metadata = null,
        ?int $authorId = null,
    ): TimelineEvent {
        return $subject->timelineEvents()->create([
            'event_type' => $eventType,
            'title' => $title,
            'description' => $description,
            'metadata' => $metadata,
            'occurred_at' => now(),
            'author_id' => $authorId ?? auth()->id(),
        ]);
    }

    public function resolveSubject(Model $model): Contact|Company|null
    {
        if ($model instanceof Contact || $model instanceof Company) {
            return $model;
        }

        if (method_exists($model, 'contact') && filled($model->getAttribute('contact_id'))) {
            return $model->getRelationValue('contact');
        }

        if (method_exists($model, 'company') && filled($model->getAttribute('company_id'))) {
            return $model->getRelationValue('company');
        }

        if (method_exists($model, 'practice') && filled($model->getAttribute('practice_id'))) {
            $practice = $model->getRelationValue('practice');

            if ($practice instanceof Model) {
                return $this->resolveSubject($practice);
            }
        }

        if (method_exists($model, 'appointment') && filled($model->getAttribute('appointment_id'))) {
            $appointment = $model->getRelationValue('appointment');

            if ($appointment instanceof Model) {
                return $this->resolveSubject($appointment);
            }
        }

        return null;
    }
}
