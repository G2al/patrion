<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AppointmentOutcome;
use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Contact;
use App\Models\Practice;

final class AppointmentSeeder extends DemoSeeder
{
    public function run(): void
    {
        $owner = $this->owner();
        $contacts = Contact::query()->get()->keyBy('email');
        $practices = Practice::query()->get()->keyBy('internal_number');
        $appointments = [
            ['title' => 'Revisione annuale del portafoglio', 'contact' => 'luigi.iommelli@example.test', 'starts_at' => today()->setTime(9, 30), 'mode' => 'in_person', 'status' => AppointmentStatus::Scheduled, 'practice' => 'PR-2026-001'],
            ['title' => 'Consegna proposta previdenziale', 'contact' => 'alessandra.costantini@example.test', 'starts_at' => today()->addDays(2)->setTime(11, 0), 'mode' => 'video_call', 'status' => AppointmentStatus::Scheduled, 'practice' => 'PR-2026-003'],
            ['title' => 'Verifica documenti protezione', 'contact' => 'giulia.bianchi@example.test', 'starts_at' => today()->subDays(3)->setTime(16, 0), 'mode' => 'phone', 'status' => AppointmentStatus::Completed, 'outcome' => AppointmentOutcome::Positive, 'practice' => 'PR-2026-002'],
            ['title' => 'Primo colloquio conoscitivo', 'contact' => 'davide.moretti@example.test', 'starts_at' => today()->subDays(5)->setTime(10, 30), 'mode' => 'video_call', 'status' => AppointmentStatus::Completed, 'outcome' => AppointmentOutcome::Negative],
        ];

        foreach ($appointments as $data) {
            Appointment::query()->updateOrCreate(['title' => $data['title']], ['contact_id' => $contacts[$data['contact']]->id, 'company_id' => null, 'practice_id' => isset($data['practice']) ? $practices[$data['practice']]->id : null, 'starts_at' => $data['starts_at'], 'ends_at' => $data['starts_at']->copy()->addHour(), 'mode' => $data['mode'], 'status' => $data['status'], 'outcome' => $data['outcome'] ?? null, 'owner_id' => $owner->id, 'reported_at' => $data['status'] === AppointmentStatus::Completed ? $data['starts_at']->copy()->addHour() : null]);
        }
    }
}
