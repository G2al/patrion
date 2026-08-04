<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\ReportAppointmentData;
use App\Enums\ActivityStatus;
use App\Enums\ActivityType;
use App\Enums\AppointmentStatus;
use App\Enums\ContactStatus;
use App\Enums\PracticeStatus;
use App\Enums\Priority;
use App\Models\Activity;
use App\Models\Appointment;
use App\Models\Practice;
use App\Services\TimelineRecorder;
use DomainException;
use Illuminate\Support\Facades\DB;

class ReportAppointment
{
    public function __construct(
        private readonly ConvertProspectToClient $convertProspect,
        private readonly TimelineRecorder $timeline,
    ) {}

    /** @return array{appointment: Appointment, practice: ?Practice, follow_up: ?Activity} */
    public function handle(Appointment $appointment, ReportAppointmentData $data, int $ownerId): array
    {
        if ($data->openPractice && ! $data->practiceTypeId) {
            throw new DomainException('La tipologia di pratica è obbligatoria.');
        }

        if ($data->followUpRequired && ! $data->nextContactAt) {
            throw new DomainException('La data del prossimo contatto è obbligatoria.');
        }

        return DB::transaction(function () use ($appointment, $data, $ownerId): array {
            $appointment->update([
                'status' => $data->occurred ? AppointmentStatus::Completed : AppointmentStatus::NoShow,
                'outcome' => $data->outcome,
                'negative_reason' => $data->negativeReason,
                'emerged_need' => $data->emergedNeed,
                'prospect_interested' => $data->prospectInterested,
                'should_convert_to_client' => $data->convertToClient,
                'should_open_practice' => $data->openPractice,
                'follow_up_required' => $data->followUpRequired,
                'next_contact_at' => $data->nextContactAt,
                'final_notes' => $data->notes,
                'reported_at' => now(),
            ]);

            $contact = $appointment->contact;

            if ($data->convertToClient && $contact?->status === ContactStatus::Prospect) {
                $this->convertProspect->handle($contact, $ownerId);
            }

            $practice = $data->openPractice ? $this->createPractice($appointment, $data, $ownerId) : null;
            $followUp = $data->followUpRequired ? $this->createFollowUp($appointment, $practice, $data, $ownerId) : null;

            if ($contact && $data->nextContactAt) {
                $contact->update(['next_follow_up_at' => $data->nextContactAt]);
            }

            $subject = $this->timeline->resolveSubject($appointment);

            if ($subject) {
                $this->timeline->record($subject, 'appointment_reported', 'Appuntamento consuntivato', metadata: [
                    'appointment_id' => $appointment->id,
                    'outcome' => $data->outcome?->value,
                ], authorId: $ownerId);
            }

            return ['appointment' => $appointment->refresh(), 'practice' => $practice, 'follow_up' => $followUp];
        });
    }

    private function createPractice(Appointment $appointment, ReportAppointmentData $data, int $ownerId): Practice
    {
        return Practice::query()->create([
            'internal_number' => $this->nextPracticeNumber(),
            'title' => $data->emergedNeed ?: "Pratica da appuntamento: {$appointment->title}",
            'practice_type_id' => $data->practiceTypeId,
            'contact_id' => $appointment->contact_id,
            'company_id' => $appointment->company_id,
            'status' => PracticeStatus::Draft,
            'priority' => Priority::Medium,
            'opened_at' => today(),
            'owner_id' => $ownerId,
        ]);
    }

    private function createFollowUp(
        Appointment $appointment,
        ?Practice $practice,
        ReportAppointmentData $data,
        int $ownerId,
    ): Activity {
        return Activity::query()->create([
            'title' => "Follow-up: {$appointment->title}",
            'description' => $data->emergedNeed,
            'type' => ActivityType::FollowUp,
            'contact_id' => $appointment->contact_id,
            'company_id' => $appointment->company_id,
            'practice_id' => $practice?->id ?? $appointment->practice_id,
            'appointment_id' => $appointment->id,
            'scheduled_at' => $data->nextContactAt,
            'due_at' => $data->nextContactAt,
            'priority' => Priority::Medium,
            'status' => ActivityStatus::Pending,
            'owner_id' => $ownerId,
        ]);
    }

    private function nextPracticeNumber(): string
    {
        $prefix = 'PR-'.now()->format('Ymd').'-';
        $sequence = Practice::query()->where('internal_number', 'like', "{$prefix}%")->count() + 1;

        do {
            $number = $prefix.str_pad((string) $sequence++, 4, '0', STR_PAD_LEFT);
        } while (Practice::query()->where('internal_number', $number)->exists());

        return $number;
    }
}
